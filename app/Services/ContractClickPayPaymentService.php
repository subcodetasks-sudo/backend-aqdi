<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Payment;
use App\Models\ServicesPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContractClickPayPaymentService
{
    public function createPaymentUrlResponse(string $uuid): JsonResponse
    {
        $contract = Contract::where('uuid', $uuid)->firstOrFail();

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
        $requestData = $this->buildClickPayPayload($contract, $cartAmount);
        $response = $this->sendClickPayPaymentRequest($requestData);
        $paymentData = $response->json();

        if (! isset($paymentData['redirect_url'])) {
            $gatewayError = $this->extractGatewayError($paymentData);

            Log::warning('ClickPay payment request rejected', [
                'contract_uuid' => $contract->uuid,
                'status_code' => $response->status(),
                'gateway_error' => $gatewayError,
                'gateway_response' => $paymentData,
            ]);

            return response()->json([
                'message' => trans('api.not_accept'),
                'gateway_error' => $gatewayError,
                'status_code' => $response->status(),
            ], 400);
        }

        return response()->json([
            'Payment_url' => $paymentData['redirect_url'],
            'payment_url' => $paymentData['redirect_url'],
            'contract_uuid' => (string) $contract->uuid,
            'cart_amount' => $cartAmount,
        ]);
    }

    /**
     * Request ClickPay redirect URL using a custom amount (employee-collected payment).
     *
     * @return array{payment_url: string, cart_amount: float, contract_uuid: string}
     */
    public function requestPaymentRedirectUrl(string $uuid, float $amount): array
    {
        $contract = Contract::where('uuid', $uuid)->firstOrFail();

        if ($amount <= 0) {
            throw new \InvalidArgumentException(trans('api.contract_payment_amount_invalid'));
        }

        $requestData = $this->buildClickPayPayload($contract, $amount);
        $response = $this->sendClickPayPaymentRequest($requestData);
        $paymentData = $response->json();

        if (! isset($paymentData['redirect_url'])) {
            $gatewayError = $this->extractGatewayError($paymentData);

            Log::warning('ClickPay employee payment request rejected', [
                'contract_uuid' => $contract->uuid,
                'amount' => $amount,
                'status_code' => $response->status(),
                'gateway_error' => $gatewayError,
                'gateway_response' => $paymentData,
            ]);

            throw new \RuntimeException($gatewayError);
        }

        return [
            'payment_url' => $paymentData['redirect_url'],
            'cart_amount' => $amount,
            'contract_uuid' => (string) $contract->uuid,
        ];
    }

    public function processIpn(Request $request, string $uuid): void
    {
        try {
            $data = $request->all();
            $contract = Contract::where('uuid', $uuid)->firstOrFail();
            $status = data_get($data, 'payment_result.response_status');

            if ($status === 'A') {
                $this->createPaymentFromGatewayData($data, 'success');
                $contract->is_completed = true;
                $contract->save();
            } elseif ($status === 'D') {
                $this->createPaymentFromGatewayData($data, 'failed');
            }
        } catch (\Throwable $e) {
            Log::error('ClickPay IPN processing failed', [
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
        $contract = Contract::where('uuid', $uuid)->firstOrFail();
        $payment = Payment::query()
            ->matchingContractUuid($uuid)
            ->latest('id')
            ->first();

        return [
            'result' => $result,
            'contract_uuid' => (string) $contract->uuid,
            'contract_id' => $contract->id,
            'is_completed' => (bool) $contract->is_completed,
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

    /**
     * @return array<string, mixed>
     */
    private function buildClickPayPayload(Contract $contract, float $cartAmount): array
    {
        return [
            'profile_id' => env('CLICKPAY_PROFILE_ID', '45644'),
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'cart_id' => $contract->uuid.'-'.now()->timestamp,
            'cart_description' => 'Contract '.$contract->uuid,
            'cart_currency' => env('CLICKPAY_CURRENCY', 'EGP'),
            'cart_amount' => $cartAmount,
            'callback' => route('callback', ['uuid' => $contract->uuid]),
        ];
    }

    private function sendClickPayPaymentRequest(array $requestData)
    {
        return Http::withHeaders([
            'Authorization' => env('CLICKPAY_SERVER_KEY', 'SHJNM2LZGN-JK6MNTGLRR-JKHGRMZBZK'),
            'Content-Type' => 'application/json',
        ])->post(
            rtrim(env('CLICKPAY_BASE_URL', 'https://secure.clickpay.com.sa'), '/').'/payment/request',
            $requestData
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createPaymentFromGatewayData(array $data, string $status): void
    {
        Payment::create([
            'name' => data_get($data, 'customer_details.name'),
            'amount' => data_get($data, 'cart_amount'),
            'contract_uuid' => data_get($data, 'cart_id'),
            'tran_currency' => data_get($data, 'tran_currency'),
            'payment_method' => data_get($data, 'payment_info.payment_method'),
            'status' => $status,
            'payment_date' => now(),
        ]);
    }

    private function extractGatewayError(mixed $paymentData): string
    {
        if (! is_array($paymentData)) {
            return 'Payment gateway returned an invalid response body.';
        }

        if (! empty($paymentData['message']) && is_string($paymentData['message'])) {
            return $paymentData['message'];
        }

        if (! empty($paymentData['payment_result']['response_message']) && is_string($paymentData['payment_result']['response_message'])) {
            return $paymentData['payment_result']['response_message'];
        }

        if (! empty($paymentData['detail']) && is_string($paymentData['detail'])) {
            return $paymentData['detail'];
        }

        return 'Payment gateway did not return redirect_url.';
    }
}
