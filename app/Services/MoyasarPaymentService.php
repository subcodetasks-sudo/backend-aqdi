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

    public function createPaymentUrlResponse(string $uuid): JsonResponse
    {
        $contract = Contract::where('uuid', $uuid)->firstOrFail();

        if (! $this->contractCanBePaid($contract)) {
            return response()->json([
                'message' => trans('api.completed_contract'),
                'success' => false,
            ], 422);
        }

        if (! $contract->contract_term_in_years) {
            return response()->json([
                'message' => trans('api.contract_period_not_set_for_payment'),
                'success' => false,
            ], 422);
        }

        $cartAmount = $this->calculateCartAmount($contract);

        if ($cartAmount <= 0) {
            return response()->json([
                'message' => trans('api.contract_payment_amount_invalid'),
                'success' => false,
                'cart_amount' => $cartAmount,
            ], 422);
        }

        $invoice = $this->createInvoice($cartAmount, 'Contract ' . $contract->uuid, (string) $contract->uuid);

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

        $redirectUrls = $this->paymentFrontendRedirectUrls((string) $contract->uuid);

        return response()->json([
            'Payment_url' => $invoice['url'],
            'payment_url' => $invoice['url'],
            'invoice_id' => $invoice['id'],
            'contract_uuid' => (string) $contract->uuid,
            'cart_amount' => $cartAmount,
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
            $paymentId = $this->resolvePaymentId($request);
            $status = $this->resolveStatus($request);

            // When a payment id is available, trust Moyasar's verified status over the request body.
            $verified = $paymentId ? $this->fetchPayment($paymentId) : null;
            if ($verified !== null && ! empty($verified['status'])) {
                $status = (string) $verified['status'];
            }

            if ($status === 'paid') {
                $contract = Contract::where('uuid', $uuid)->first();

                if ($this->hasSuccessfulPayment($uuid)) {
                    ContractPaidByEmployee::query()
                        ->where('contract_uuid', $uuid)
                        ->where('is_paid', false)
                        ->update(['is_paid' => true]);

                    if ($contract && ! $contract->is_completed) {
                        $contract->is_completed = true;
                        $contract->save();
                    }

                    return;
                }

                $this->persistPayment($request, $verified, $uuid, 'success');

                ContractPaidByEmployee::query()
                    ->where('contract_uuid', $uuid)
                    ->where('is_paid', false)
                    ->update(['is_paid' => true]);

                if ($contract) {
                    $contract->is_completed = true;
                    $contract->save();
                }

                return;
            }

            if (in_array($status, ['failed', 'voided', 'refunded'], true)) {
                $this->persistPayment($request, $verified, $uuid, 'failed');
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
    public function paymentStatusPayload(string $uuid, string $result): array
    {
        $contract = Contract::where('uuid', $uuid)->first();
        $employeePaidRecord = ContractPaidByEmployee::query()
            ->where('contract_uuid', $uuid)
            ->first();

        if (! $contract && ! $employeePaidRecord) {
            abort(404, trans('api.contract_not_found'));
        }

        $payment = Payment::query()
            ->matchingContractUuid($uuid)
            ->latest('id')
            ->first();

        return [
            'result' => $result,
            'contract_uuid' => $uuid,
            'contract_id' => $contract?->id,
            'is_completed' => $contract ? (bool) $contract->is_completed : false,
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

    public function calculateCartAmount(Contract $contract): float
    {
        $servicesTotal = ServicesPricing::where('contract_type', $contract->contract_type)->sum('price');
        $contractBaseTotal = (float) $contract->getPriceContractAttribute() + (float) $servicesTotal;
        $couponDiscount = $this->resolveCouponDiscount($contract, $contractBaseTotal);
        $netContractTotal = max(0, $contractBaseTotal - $couponDiscount);

        $contractPeriodPrice = 0.0;
        if ($contract->contract_term_in_years) {
            $contractPeriodPrice = (float) (ContractPeriod::query()
                ->where('contract_type', $contract->contract_type)
                ->where('id', $contract->contract_term_in_years)
                ->value('price') ?? 0);
        }

        return (float) max(0, $netContractTotal + max(0, $contractPeriodPrice));
    }

    /*
    |--------------------------------------------------------------------------
    | Moyasar gateway helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{payment_url: string, cart_amount: float, contract_uuid: string}
     */
    private function createInvoiceOrFail(float $amount, string $description, string $contractUuid): array
    {
        $invoice = $this->createInvoice($amount, $description, $contractUuid);

        if (! $invoice['success']) {
            Log::warning('Moyasar invoice request rejected', [
                'contract_uuid' => $contractUuid,
                'amount' => $amount,
                'status_code' => $invoice['status'],
                'gateway_error' => $invoice['message'],
            ]);

            throw new \RuntimeException($invoice['message'] ?? trans('api.not_accept'));
        }

        $redirectUrls = $this->paymentFrontendRedirectUrls($contractUuid);

        return [
            'payment_url' => (string) $invoice['url'],
            'cart_amount' => $amount,
            'contract_uuid' => $contractUuid,
            'payment_success_url' => $redirectUrls['success'],
            'payment_error_url' => $redirectUrls['error'],
        ];
    }

    /**
     * @return array{success: string, error: string}
     */
    private function paymentFrontendRedirectUrls(string $contractUuid): array
    {
        $base = rtrim((string) config('services.moyasar.payment_frontend_url', 'http://localhost:3000'), '/');

        return [
            'success' => "{$base}/payment/success/{$contractUuid}",
            'error' => "{$base}/payment/error/{$contractUuid}",
        ];
    }

    /**
     * Create a Moyasar invoice and normalise the useful bits.
     *
     * @return array{success: bool, status: int, url: string|null, id: string|null, message: string|null}
     */
    private function createInvoice(float $amount, string $description, string $contractUuid): array
    {
        $redirectUrls = $this->paymentFrontendRedirectUrls($contractUuid);

        $response = $this->buildRequest('POST', '/v1/invoices', [
            'amount' => $this->toMinorUnits($amount),
            'currency' => $this->currency,
            'description' => $description,
            'callback_url' => route('callback', ['uuid' => $contractUuid]),
            'success_url' => $redirectUrls['success'],
            'back_url' => $redirectUrls['error'],
            'metadata' => [
                'contract_uuid' => $contractUuid,
            ],
        ]);

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

    private function resolvePaymentId(Request $request): ?string
    {
        $id = $request->input('data.id') ?? $request->input('id');

        return $id !== null ? (string) $id : null;
    }

    private function resolveStatus(Request $request): ?string
    {
        $status = $request->input('data.status') ?? $request->input('status');

        return $status !== null ? (string) $status : null;
    }

    /**
     * @param  array<string, mixed>|null  $verified
     */
    private function persistPayment(Request $request, ?array $verified, string $uuid, string $status): void
    {
        $source = is_array($verified['source'] ?? null) ? $verified['source'] : [];
        $metadata = is_array($verified['metadata'] ?? null) ? $verified['metadata'] : [];

        $amount = isset($verified['amount'])
            ? (float) $verified['amount'] / 100
            : (float) $request->input('amount', 0);

        Payment::create([
            'name' => $metadata['name'] ?? $request->input('metadata.name'),
            'amount' => $amount,
            'contract_uuid' => $metadata['contract_uuid'] ?? $uuid,
            'tran_currency' => $verified['currency'] ?? $this->currency,
            'payment_method' => $source['type'] ?? null,
            'status' => $status,
            'payment_date' => now(),
        ]);
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
        return ! $contract->is_completed && ! $this->hasSuccessfulPayment((string) $contract->uuid);
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
