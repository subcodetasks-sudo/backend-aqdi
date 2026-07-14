<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Contract;
use App\Models\ContractPaidByEmployee;
use App\Models\ContractPeriod;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Payment;
use App\Models\ServicesPricing;
use App\Support\DocFee;
use App\Support\MeterFees;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoyasarPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    private string $currency;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.moyasar.base_url', 'https://api.moyasar.com'), '/');
        $this->currency = (string) config('services.moyasar.currency', 'SAR');

        $secretKey = (string) config('services.moyasar.secret_key');

        $this->headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            // Moyasar uses HTTP Basic auth: secret key as username, empty password.
            'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
        ];
    }

    public function createPaymentUrlResponse(string $uuid, string $client = 'web'): JsonResponse
    {
        $client = $this->normalizePaymentClient($client);
        $contract = Contract::where('uuid', $uuid)->firstOrFail();

        if (! $this->contractCanBePaid($contract)) {
            return response()->json([
                'message' => trans('api.completed_contract'),
                'success' => false,
            ], 422);
        }

        if (! $contract->contract_term_in_years && ! $contract->duration_preset && ! $contract->total_months) {
            return response()->json([
                'message' => trans('api.contract_period_not_set_for_payment'),
                'success' => false,
            ], 422);
        }

        $meterFees = MeterFees::forContract($contract);
        $docFeeSummary = DocFee::forContract($contract);
        $cartAmount = $this->calculateCartAmount($contract);

        if ($cartAmount <= 0) {
            return response()->json([
                'message' => trans('api.contract_payment_amount_invalid'),
                'success' => false,
                'cart_amount' => $cartAmount,
                'meter_fees_total' => $meterFees['meter_fees_total'],
                'doc_fee' => $docFeeSummary['doc_fee'] ?? 0,
            ], 422);
        }

        $invoice = $this->createInvoice(
            $cartAmount,
            'Contract ' . $contract->uuid,
            (string) $contract->uuid,
            $client
        );

        if (! $invoice['success']) {
            Log::warning('Moyasar invoice request rejected', [
                'contract_uuid' => $contract->uuid,
                'status_code' => $invoice['status'],
                'gateway_error' => $invoice['message'],
            ]);

            return response()->json([
                'message' => trans('api.not_accept'),
                'gateway_error' => $invoice['message'],
                'status_code' => $invoice['status'],
            ], 400);
        }

        $redirectUrls = $this->paymentFrontendRedirectUrls((string) $contract->uuid, $client);

        return response()->json([
            'Payment_url' => $invoice['url'],
            'payment_url' => $invoice['url'],
            'invoice_id' => $invoice['id'],
            'contract_uuid' => (string) $contract->uuid,
            'cart_amount' => $cartAmount,
            'meter_fees_total' => $meterFees['meter_fees_total'],
            'electricity_meter_fee' => $meterFees['electricity_meter_fee'],
            'water_meter_fee' => $meterFees['water_meter_fee'],
            'doc_fee' => $docFeeSummary['doc_fee'] ?? null,
            'doc_fee_lines' => $docFeeSummary['doc_fee_lines'] ?? [],
            'payment_success_url' => $redirectUrls['success'],
            'payment_error_url' => $redirectUrls['error'],
        ]);
    }

    public function requestPaymentRedirectUrl(string $uuid, float $amount): array
    {
        $contract = Contract::where('uuid', $uuid)->firstOrFail();

        if (! $this->contractCanBePaid($contract)) {
            throw new \InvalidArgumentException(trans('api.completed_contract'));
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException(trans('api.contract_payment_amount_invalid'));
        }

        return $this->createInvoiceOrFail($amount, 'Contract ' . $contract->uuid, (string) $contract->uuid);
    }

    public function requestPaymentRedirectUrlWithoutContract(string $contractUuid, float $amount): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(trans('api.contract_payment_amount_invalid'));
        }

        return $this->createInvoiceOrFail($amount, 'Employee payment ' . $contractUuid, $contractUuid);
    }

    public function processIpn(Request $request, string $uuid): void
    {
        try {
            $uuid = $this->normalizeContractUuid($uuid);
            $paymentId = $this->resolvePaymentId($request);
            $invoiceId = $this->resolveInvoiceId($request);
            $status = $this->resolveStatus($request);

            $verified = $this->resolveVerifiedGatewayPayment($uuid, $paymentId, $invoiceId);

            if ($verified !== null && ! empty($verified['status'])) {
                $status = (string) $verified['status'];
            }

            // Invoice callback/webhook body may nest status under the invoice object.
            if ($status === null || $status === '') {
                $status = $this->resolveStatusFromPayload($request);
            }

            // If Moyasar redirect/query had no status, still reconcile from gateway by contract uuid.
            if (($status === null || $status === '') && ! $this->hasSuccessfulPayment($uuid)) {
                $verified = $verified ?? $this->resolveGatewayPaymentForSync($uuid, $paymentId, $invoiceId);
                if ($verified !== null && ! empty($verified['status'])) {
                    $status = (string) $verified['status'];
                }
            }

            if ($status === 'paid') {
                if ($verified === null) {
                    $verified = $this->resolveGatewayPaymentForSync($uuid, $paymentId, $invoiceId);
                }

                if ($this->hasSuccessfulPayment($uuid)) {
                    $this->markContractAsCompleted($uuid);

                    return;
                }

                // Never mark paid from redirect query alone — gateway must confirm.
                $gatewayPaid = is_array($verified)
                    && strtolower((string) ($verified['status'] ?? '')) === 'paid';

                if (! $gatewayPaid) {
                    Log::warning('Moyasar paid signal ignored without gateway confirmation', [
                        'contract_uuid' => $uuid,
                        'payment_id' => $paymentId,
                        'invoice_id' => $invoiceId,
                    ]);

                    return;
                }

                $this->persistPaymentFromGateway($verified, $uuid, 'success');
                $this->markContractAsCompleted($uuid);

                return;
            }

            if (in_array($status, ['failed', 'voided', 'refunded'], true)) {
                // Never overwrite a successful local payment with a later failed callback.
                if ($this->hasSuccessfulPayment($uuid)) {
                    $this->markContractAsCompleted($uuid);

                    return;
                }

                if ($verified !== null) {
                    $this->persistPaymentFromGateway($verified, $uuid, 'failed');
                } else {
                    $this->persistPayment($request, null, $uuid, 'failed');
                }

                // Failed attempt must not leave the contract locked as completed.
                $this->revertContractCompletionWithoutSuccessfulPayment($uuid);
            }
        } catch (\Throwable $e) {
            Log::error('Moyasar IPN processing failed', [
                'contract_uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentStatusPayload(
        string $uuid,
        string $result,
        ?string $paymentId = null,
        ?string $invoiceId = null
    ): array
    {
        $uuid = $this->normalizeContractUuid($uuid);
        $sync = $this->syncGatewayPaymentStatus($uuid, $paymentId, $invoiceId);

        $employeePaidRecord = ContractPaidByEmployee::query()
            ->where('contract_uuid', $uuid)
            ->first();
        $contract = Contract::where('uuid', $uuid)->first();

        if (! $contract && ! $employeePaidRecord) {
            abort(404, trans('api.contract_not_found'));
        }

        // After sync: confirm payment only from success rows / gateway / employee paid,
        // never from is_completed alone (blocked retry after failed payment).
        $payment = Payment::query()
            ->matchingContractUuid($uuid)
            ->latest('id')
            ->first();

        $paymentStatus = (string) ($payment?->status ?? '');
        $paymentConfirmed = $this->hasSuccessfulPayment($uuid)
            || ($employeePaidRecord ? (bool) $employeePaidRecord->is_paid : false)
            || (($sync['synced'] ?? false) && ($sync['status'] ?? null) === 'success');

        if (! $paymentConfirmed) {
            $this->revertContractCompletionWithoutSuccessfulPayment($uuid);
            $contract = $contract?->fresh();
        } else {
            $contract = $contract?->fresh();
        }

        $resolvedResult = $paymentConfirmed
            ? 'success'
            : ($paymentStatus === 'failed' ? 'error' : $result);

        return [
            'result' => $result,
            'resolved_result' => $resolvedResult,
            'contract_uuid' => $uuid,
            'contract_id' => $contract?->id,
            'is_completed' => $contract ? (bool) $contract->is_completed : false,
            'payment_confirmed' => $paymentConfirmed,
            'sync' => $sync,
            'employee_paid_record' => $employeePaidRecord ? [
                'id' => $employeePaidRecord->id,
                'employee_id' => $employeePaidRecord->employee_id,
                'customer_mobile' => $employeePaidRecord->customer_mobile,
                'amount' => (float) $employeePaidRecord->amount,
                'is_paid' => (bool) $employeePaidRecord->is_paid,
                'notes' => $employeePaidRecord->notes,
            ] : null,
            'payment' => $payment ? [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date,
            ] : null,
        ];
    }

    public function isPaymentConfirmed(
        string $uuid,
        ?string $paymentId = null,
        ?string $invoiceId = null
    ): bool {
        try {
            $payload = $this->paymentStatusPayload($uuid, 'return', $paymentId, $invoiceId);

            return (bool) ($payload['payment_confirmed'] ?? false);
        } catch (\Throwable) {
            return $this->hasSuccessfulPayment($uuid);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function syncGatewayPaymentStatus(string $uuid, ?string $paymentId = null, ?string $invoiceId = null): array
    {
        $uuid = $this->normalizeContractUuid($uuid);
        $gatewayPayment = $this->resolveGatewayPaymentForSync($uuid, $paymentId, $invoiceId);

        if ($gatewayPayment === null) {
            Log::warning('Moyasar gateway sync could not resolve payment', [
                'contract_uuid' => $uuid,
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
            ]);

            return [
                'contract_uuid' => $uuid,
                'synced' => false,
                'reason' => 'gateway_payment_not_found',
            ];
        }

        $gatewayStatus = (string) ($gatewayPayment['status'] ?? '');
        $metadata = is_array($gatewayPayment['metadata'] ?? null) ? $gatewayPayment['metadata'] : [];
        $contractUuid = (string) ($metadata['contract_uuid'] ?? $uuid);

        if ($gatewayStatus === 'paid') {
            $this->persistPaymentFromGateway($gatewayPayment, $contractUuid, 'success');
            $this->markContractAsCompleted($contractUuid);

            return [
                'contract_uuid' => $contractUuid,
                'synced' => true,
                'status' => 'success',
            ];
        }

        if (in_array($gatewayStatus, ['failed', 'voided', 'refunded'], true)) {
            $this->persistPaymentFromGateway($gatewayPayment, $contractUuid, 'failed');
            $this->revertContractCompletionWithoutSuccessfulPayment($contractUuid);

            return [
                'contract_uuid' => $contractUuid,
                'synced' => true,
                'status' => 'failed',
            ];
        }

        return [
            'contract_uuid' => $uuid,
            'synced' => false,
            'reason' => 'gateway_status_not_final',
            'gateway_status' => $gatewayStatus,
        ];
    }

    public function calculateCartAmount(Contract $contract): float
    {
        $servicesTotal = ServicesPricing::where('contract_type', $contract->contract_type)->sum('price');
        $contractBaseTotal = (float) $contract->getPriceContractAttribute() + (float) $servicesTotal;
        $couponDiscount = $this->resolveCouponDiscount($contract, $contractBaseTotal);
        $netContractTotal = max(0, $contractBaseTotal - $couponDiscount);

        $docFeeSummary = DocFee::forContract($contract);
        if ($docFeeSummary !== null) {
            $contractPeriodPrice = (float) $docFeeSummary['doc_fee'];
        } else {
            $contractPeriodPrice = 0.0;
            if ($contract->contract_term_in_years) {
                $contractPeriodPrice = (float) (ContractPeriod::query()
                    ->where('contract_type', $contract->contract_type)
                    ->where('id', $contract->contract_term_in_years)
                    ->value('price') ?? 0);
            }
        }

        $meterFeesTotal = MeterFees::totalForContract($contract);

        return (float) max(0, $netContractTotal + max(0, $contractPeriodPrice) + $meterFeesTotal);
    }

    /*
    |--------------------------------------------------------------------------
    | Moyasar gateway helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{payment_url: string, cart_amount: float, contract_uuid: string, payment_success_url: ?string, payment_error_url: ?string}
     */
    private function createInvoiceOrFail(float $amount, string $description, string $contractUuid, string $client = 'web'): array
    {
        $client = $this->normalizePaymentClient($client);
        $invoice = $this->createInvoice($amount, $description, $contractUuid, $client);

        if (! $invoice['success']) {
            Log::warning('Moyasar invoice request rejected', [
                'contract_uuid' => $contractUuid,
                'amount' => $amount,
                'status_code' => $invoice['status'],
                'gateway_error' => $invoice['message'],
            ]);

            throw new \RuntimeException($invoice['message'] ?? trans('api.not_accept'));
        }

        $redirectUrls = $this->paymentFrontendRedirectUrls($contractUuid, $client);

        return [
            'payment_url' => (string) $invoice['url'],
            'cart_amount' => $amount,
            'contract_uuid' => $contractUuid,
            'payment_success_url' => $redirectUrls['success'],
            'payment_error_url' => $redirectUrls['error'],
        ];
    }

    /**
     * Always give Moyasar success/back URLs so the browser leaves the gateway
     * "Invoice Paid" page and lands on success / failed screens.
     *
     * App with PAYMENT_APP_* templates → deep links.
     * Otherwise → backend status routes (process IPN) → frontend templates.
     *
     * @return array{success: string, error: string}
     */
    private function paymentFrontendRedirectUrls(string $contractUuid, string $client = 'web'): array
    {
        $client = $this->normalizePaymentClient($client);

        if ($client === 'app') {
            $successTemplate = (string) config('services.moyasar.payment_app_success_url_template', '');
            $errorTemplate = (string) config('services.moyasar.payment_app_error_url_template', '');

            if ($successTemplate !== '' && $errorTemplate !== '') {
                return [
                    'success' => str_replace('{uuid}', $contractUuid, $successTemplate),
                    'error' => str_replace('{uuid}', $contractUuid, $errorTemplate),
                ];
            }
        }

        // One smart redirect for Moyasar (paid → success screen, else → failed screen).
        return [
            'success' => route('status.result', ['uuid' => $contractUuid]),
            'error' => route('status.result', ['uuid' => $contractUuid]),
        ];
    }

    private function normalizePaymentClient(string $client): string
    {
        $client = strtolower(trim($client));

        return in_array($client, ['app', 'mobile', 'ios', 'android'], true) ? 'app' : 'web';
    }

    /**
     * Create a Moyasar invoice and normalise the useful bits.
     *
     * @return array{success: bool, status: int, url: string|null, id: string|null, message: string|null}
     */
    private function createInvoice(float $amount, string $description, string $contractUuid, string $client = 'web'): array
    {
        $redirectUrls = $this->paymentFrontendRedirectUrls($contractUuid, $client);

        $payload = [
            'amount' => $this->toMinorUnits($amount),
            'currency' => $this->currency,
            'description' => $description,
            'callback_url' => route('callback', ['uuid' => $contractUuid]),
            'metadata' => [
                'contract_uuid' => $contractUuid,
            ],
        ];

        // Always attach redirects so Moyasar leaves the invoice page.
        $payload['success_url'] = $redirectUrls['success'];
        $payload['back_url'] = $redirectUrls['error'];

        $response = $this->buildRequest('POST', '/v1/invoices', $payload);

        $data = $response['data'] ?? [];
        $url = is_array($data) ? ($data['url'] ?? null) : null;

        return [
            'success' => $response['success'] && ! empty($url),
            'status' => $response['status'],
            'url' => $url,
            'id' => is_array($data) ? ($data['id'] ?? null) : null,
            'message' => ($response['success'] && empty($url))
                ? 'Payment gateway did not return a payment url.'
                : $response['message'],
        ];
    }

    /**
     * Fetch a payment from Moyasar to verify its real status.
     *
     * @return array<string, mixed>|null
     */
    private function fetchPayment(string $paymentId): ?array
    {
        $response = $this->buildRequest('GET', '/v1/payments/' . $paymentId);

        return $response['success'] && is_array($response['data']) ? $response['data'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchInvoice(string $invoiceId): ?array
    {
        $response = $this->buildRequest('GET', '/v1/invoices/' . $invoiceId);

        return $response['success'] && is_array($response['data']) ? $response['data'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveGatewayPaymentForSync(string $contractUuid, ?string $paymentId, ?string $invoiceId): ?array
    {
        $verified = $this->resolveVerifiedGatewayPayment($contractUuid, $paymentId, $invoiceId);
        if ($verified !== null) {
            // Never invent a "paid" match from other invoices when this callback
            // already resolved to a failed/voided/refunded attempt.
            $status = strtolower((string) ($verified['status'] ?? ''));
            if (in_array($status, ['failed', 'voided', 'refunded', 'paid'], true)) {
                return $verified;
            }
        }

        // Only search other gateway rows when we have no redirect id at all.
        $hasExplicitId = (is_string($paymentId) && $paymentId !== '')
            || (is_string($invoiceId) && $invoiceId !== '');

        if ($hasExplicitId && $verified !== null) {
            return $verified;
        }

        if ($hasExplicitId && $verified === null) {
            // Explicit id was given but not found/paid — do not upgrade via stale paid search.
            return $this->fetchLatestPaymentByContractUuid($contractUuid);
        }

        $invoicePayment = $this->extractPaidPaymentFromInvoice(
            $this->fetchLatestInvoiceByContractUuid($contractUuid),
            $contractUuid
        );
        if ($invoicePayment !== null) {
            return $invoicePayment;
        }

        return $this->fetchLatestPaymentByContractUuid($contractUuid);
    }

    /**
     * Moyasar may send `id` as either a payment id or an invoice id.
     *
     * @return array<string, mixed>|null
     */
    private function resolveVerifiedGatewayPayment(
        string $contractUuid,
        ?string $paymentId,
        ?string $invoiceId
    ): ?array {
        $candidates = array_values(array_unique(array_filter([
            $paymentId,
            $invoiceId,
        ], static fn ($value) => is_string($value) && $value !== '')));

        foreach ($candidates as $candidateId) {
            $payment = $this->fetchPayment($candidateId);
            if ($payment !== null) {
                return $payment;
            }

            $invoice = $this->fetchInvoice($candidateId);
            if ($invoice === null) {
                continue;
            }

            $fromInvoice = $this->extractPaidPaymentFromInvoice($invoice, $contractUuid);
            if ($fromInvoice !== null) {
                return $fromInvoice;
            }

            // Invoice exists but is not paid — return its real final payment/status
            // so failed redirects are not upgraded by a different "paid" invoice.
            $failedOrPending = $this->extractAnyPaymentFromInvoice($invoice, $contractUuid);
            if ($failedOrPending !== null) {
                return $failedOrPending;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $invoice
     * @return array<string, mixed>|null
     */
    private function extractPaidPaymentFromInvoice(?array $invoice, string $contractUuid): ?array
    {
        if ($invoice === null) {
            return null;
        }

        $invoiceMetadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $resolvedContractUuid = (string) ($invoiceMetadata['contract_uuid'] ?? $contractUuid);

        $payments = is_array($invoice['payments'] ?? null) ? $invoice['payments'] : [];
        foreach ($payments as $payment) {
            if (! is_array($payment) || ($payment['status'] ?? null) !== 'paid') {
                continue;
            }

            $paymentMetadata = is_array($payment['metadata'] ?? null) ? $payment['metadata'] : [];

            return array_merge($payment, [
                'metadata' => array_merge($paymentMetadata, [
                    'contract_uuid' => (string) ($paymentMetadata['contract_uuid'] ?? $resolvedContractUuid),
                ]),
                'invoice_id' => $invoice['id'] ?? null,
            ]);
        }

        if (($invoice['status'] ?? null) !== 'paid') {
            return null;
        }

        return [
            'id' => $invoice['id'] ?? null,
            'status' => 'paid',
            'amount' => $invoice['amount'] ?? 0,
            'currency' => $invoice['currency'] ?? $this->currency,
            'metadata' => array_merge($invoiceMetadata, [
                'contract_uuid' => $resolvedContractUuid,
            ]),
            'source' => [],
            'invoice_id' => $invoice['id'] ?? null,
        ];
    }

    /**
     * Prefer final unsuccessful payments on an invoice (failed/voided/refunded),
     * otherwise the latest payment, otherwise the invoice status itself.
     *
     * @param  array<string, mixed>  $invoice
     * @return array<string, mixed>|null
     */
    private function extractAnyPaymentFromInvoice(array $invoice, string $contractUuid): ?array
    {
        $invoiceMetadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $resolvedContractUuid = (string) ($invoiceMetadata['contract_uuid'] ?? $contractUuid);
        $payments = is_array($invoice['payments'] ?? null) ? $invoice['payments'] : [];

        $failed = null;
        $latest = null;
        $latestTs = 0;

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                continue;
            }

            $status = strtolower((string) ($payment['status'] ?? ''));
            $ts = strtotime((string) ($payment['created_at'] ?? '')) ?: 0;
            if ($ts >= $latestTs) {
                $latestTs = $ts;
                $latest = $payment;
            }

            if (in_array($status, ['failed', 'voided', 'refunded'], true)) {
                $failed = $payment;
            }
        }

        $chosen = $failed ?? $latest;
        if ($chosen !== null) {
            $paymentMetadata = is_array($chosen['metadata'] ?? null) ? $chosen['metadata'] : [];

            return array_merge($chosen, [
                'metadata' => array_merge($paymentMetadata, [
                    'contract_uuid' => (string) ($paymentMetadata['contract_uuid'] ?? $resolvedContractUuid),
                ]),
                'invoice_id' => $invoice['id'] ?? null,
            ]);
        }

        $invoiceStatus = strtolower((string) ($invoice['status'] ?? ''));
        if ($invoiceStatus === '' || $invoiceStatus === 'paid') {
            return null;
        }

        return [
            'id' => $invoice['id'] ?? null,
            'status' => $invoiceStatus,
            'amount' => $invoice['amount'] ?? 0,
            'currency' => $invoice['currency'] ?? $this->currency,
            'metadata' => array_merge($invoiceMetadata, [
                'contract_uuid' => $resolvedContractUuid,
            ]),
            'source' => [],
            'invoice_id' => $invoice['id'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLatestInvoiceByContractUuid(string $contractUuid): ?array
    {
        $byMetadata = $this->fetchLatestGatewayResourceByMetadata('/v1/invoices', 'invoices', $contractUuid);
        if ($byMetadata !== null) {
            return $byMetadata;
        }

        return $this->fetchLatestInvoiceByDescription($contractUuid);
    }

    /**
     * Fallback when Moyasar metadata filter is unavailable: match description "Contract {uuid}".
     *
     * @return array<string, mixed>|null
     */
    private function fetchLatestInvoiceByDescription(string $contractUuid): ?array
    {
        $response = $this->buildRequest('GET', '/v1/invoices', [
            'limit' => 50,
        ], 'query');

        if (! $response['success'] || ! isset($response['data']) || ! is_array($response['data'])) {
            return null;
        }

        $rows = array_is_list($response['data'])
            ? $response['data']
            : ($response['data']['invoices'] ?? $response['data']['data'] ?? []);

        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $needle = 'Contract '.$contractUuid;
        $matches = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : [];
            $description = (string) ($row['description'] ?? '');
            $metaUuid = (string) ($metadata['contract_uuid'] ?? '');

            // Exact match only — str_contains on 6-digit uuids falsely links other contracts.
            if ($metaUuid === $contractUuid || $description === $needle) {
                $matches[] = $row;
            }
        }

        if ($matches === []) {
            return null;
        }

        // Newest invoice for this contract (do not prefer unrelated older "paid" rows).
        usort($matches, static function (array $a, array $b): int {
            return strtotime((string) ($b['created_at'] ?? '')) <=> strtotime((string) ($a['created_at'] ?? ''));
        });

        return $matches[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLatestPaymentByContractUuid(string $contractUuid): ?array
    {
        return $this->fetchLatestGatewayResourceByMetadata('/v1/payments', 'payments', $contractUuid);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLatestGatewayResourceByMetadata(string $endpoint, string $collectionKey, string $contractUuid): ?array
    {
        $response = $this->buildRequest('GET', $endpoint, [
            'metadata[contract_uuid]' => $contractUuid,
        ], 'query');

        if (! $response['success'] || ! isset($response['data']) || ! is_array($response['data'])) {
            return null;
        }

        $rows = array_is_list($response['data'])
            ? $response['data']
            : ($response['data'][$collectionKey] ?? $response['data']['data'] ?? []);

        if (! is_array($rows) || $rows === []) {
            return null;
        }

        usort($rows, static function (mixed $a, mixed $b): int {
            $aCreated = is_array($a) ? strtotime((string) ($a['created_at'] ?? '')) : 0;
            $bCreated = is_array($b) ? strtotime((string) ($b['created_at'] ?? '')) : 0;

            return $bCreated <=> $aCreated;
        });

        return is_array($rows[0] ?? null) ? $rows[0] : null;
    }

    private function resolvePaymentId(Request $request): ?string
    {
        $id = $request->query('id')
            ?? $request->input('data.id')
            ?? $request->input('id')
            ?? $request->input('payment_id');

        return $id !== null && $id !== '' ? (string) $id : null;
    }

    private function resolveStatus(Request $request): ?string
    {
        $status = $request->query('status')
            ?? $request->input('data.status')
            ?? $request->input('status');

        return $status !== null && $status !== '' ? strtolower((string) $status) : null;
    }

    private function resolveInvoiceId(Request $request): ?string
    {
        $id = $request->query('invoice_id')
            ?? $request->input('data.invoice_id')
            ?? $request->input('invoice_id')
            ?? $request->input('invoice.id');

        return $id !== null && $id !== '' ? (string) $id : null;
    }

    private function resolveStatusFromPayload(Request $request): ?string
    {
        $candidates = [
            $request->input('status'),
            $request->input('data.status'),
            $request->input('invoice.status'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return strtolower($candidate);
            }
        }

        return null;
    }

    /**
     * Accept contract uuid or numeric contract id in callback URLs.
     */
    private function normalizeContractUuid(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return $key;
        }

        if (Contract::query()->where('uuid', $key)->exists()) {
            return $key;
        }

        if (ctype_digit($key)) {
            $uuid = Contract::query()->whereKey((int) $key)->value('uuid');
            if (is_string($uuid) && $uuid !== '') {
                return $uuid;
            }
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>|null  $verified
     */
    private function persistPayment(Request $request, ?array $verified, string $uuid, string $status): void
    {
        $source = is_array($verified['source'] ?? null) ? $verified['source'] : [];
        $metadata = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];
        $contractUuid = (string) ($metadata['contract_uuid'] ?? $uuid);
        $amount = $this->normalizeGatewayAmount(
            $verified['amount'] ?? $request->input('amount', 0)
        );

        if (Payment::query()->matchingContractUuid($contractUuid)->where('status', $status)->exists()) {
            return;
        }

        Payment::create([
            'name' => $this->resolvePaymentName(
                $metadata['name'] ?? $request->input('metadata.name'),
                $contractUuid
            ),
            'amount' => $amount,
            'contract_uuid' => $contractUuid,
            'tran_currency' => $verified['currency'] ?? $this->currency,
            'payment_method' => $source['type'] ?? 'moyasar',
            'status' => $status,
            'payment_date' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $gatewayPayment
     */
    private function persistPaymentFromGateway(array $gatewayPayment, string $uuid, string $status): void
    {
        $source = is_array($gatewayPayment['source'] ?? null) ? $gatewayPayment['source'] : [];
        $metadata = is_array($gatewayPayment['metadata'] ?? null) ? $gatewayPayment['metadata'] : [];
        $contractUuid = (string) ($metadata['contract_uuid'] ?? $uuid);

        if (Payment::query()->matchingContractUuid($contractUuid)->where('status', $status)->exists()) {
            return;
        }

        Payment::create([
            'name' => $this->resolvePaymentName($metadata['name'] ?? null, $contractUuid),
            'amount' => $this->normalizeGatewayAmount($gatewayPayment['amount'] ?? 0),
            'contract_uuid' => $contractUuid,
            'tran_currency' => $gatewayPayment['currency'] ?? $this->currency,
            'payment_method' => $source['type'] ?? 'moyasar',
            'status' => $status,
            'payment_date' => now(),
        ]);
    }

    private function resolvePaymentName(mixed $name, string $contractUuid): string
    {
        $resolved = is_string($name) ? trim($name) : '';

        return $resolved !== '' ? $resolved : 'Contract ' . $contractUuid;
    }

    private function normalizeGatewayAmount(mixed $amount): float
    {
        $numeric = (float) $amount;

        if ($numeric <= 0) {
            return 0.0;
        }

        return round($numeric / 100, 2);
    }

    private function markContractAsCompleted(string $uuid): void
    {
        // Only complete after a local successful payment row exists.
        if (! $this->hasSuccessfulPayment($uuid)) {
            return;
        }

        $contract = Contract::where('uuid', $uuid)->first();
        $becameCompleted = false;
        if ($contract && ! $contract->is_completed) {
            $contract->is_completed = true;
            $contract->save();
            $becameCompleted = true;
        }

        ContractPaidByEmployee::query()
            ->where('contract_uuid', $uuid)
            ->where('is_paid', false)
            ->update(['is_paid' => true]);

        if ($becameCompleted && $contract) {
            $paidAmount = (float) (Payment::query()
                ->successfulMatchingContractUuid($uuid)
                ->latest('id')
                ->value('amount') ?? 0);

            try {
                app(FirebaseNotificationService::class)
                    ->notifyEmployeesOfNewContract($contract->fresh(['user']), $paidAmount > 0 ? $paidAmount : null);
            } catch (\Throwable $e) {
                Log::warning('Failed to notify employees of paid contract', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Clear a stale is_completed flag when there is no successful payment.
     * Prevents "العقد مكتمل، فشل التعديل" after a failed gateway attempt.
     */
    private function revertContractCompletionWithoutSuccessfulPayment(string $uuid): void
    {
        if ($this->hasSuccessfulPayment($uuid)) {
            return;
        }

        if (ContractPaidByEmployee::query()
            ->where('contract_uuid', $uuid)
            ->where('is_paid', true)
            ->exists()
        ) {
            return;
        }

        $contract = Contract::where('uuid', $uuid)->first();
        if ($contract && $contract->is_completed) {
            $contract->is_completed = false;
            $contract->save();
        }
    }

    private function resolveCouponDiscount(Contract $contract, float $totalContractPrice): float
    {
        $contractCoupon = CouponUsage::where('contract_uuid', $contract->uuid)->first();
        if (! $contractCoupon) {
            return 0.0;
        }

        $coupon = Coupon::find($contractCoupon->coupon_id);
        if (! $coupon) {
            return 0.0;
        }

        return (float) ($coupon->type_coupon === 'ratio'
            ? ($totalContractPrice * $coupon->value_coupon / 100)
            : $coupon->value_coupon);
    }

    private function contractCanBePaid(Contract $contract): bool
    {
        $uuid = (string) $contract->uuid;

        if ($this->hasSuccessfulPayment($uuid)) {
            return false;
        }

        // Stale completion without a successful payment must not block retry.
        $this->revertContractCompletionWithoutSuccessfulPayment($uuid);
        $contract->refresh();

        return ! $contract->is_completed;
    }

    private function hasSuccessfulPayment(string $contractUuid): bool
    {
        return Payment::query()
            ->successfulMatchingContractUuid($contractUuid)
            ->exists();
    }

    /**
     * Convert a major-unit amount (e.g. SAR) to Moyasar minor units (halalas).
     */
    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
