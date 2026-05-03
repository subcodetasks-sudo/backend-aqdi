<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Models\CouponUsage;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\ServicesPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TaqnyatSms;

class PaymentController extends Controller
{
    use Responser;

    public function index(Request $request, $uuid)
    {
        $contract = Contract::where('uuid', $uuid)->firstOrFail();

        $cartAmount = $this->calculateCartAmount($contract);
        $requestData = $this->buildClickPayPayload($contract, $cartAmount);
        $response = $this->sendClickPayPaymentRequest($requestData);
        $paymentData = $response->json();

        if (! isset($paymentData['redirect_url'])) {
            $gatewayError = $this->extractGatewayError($paymentData);

            Log::warning('ClickPay payment request rejected', [
                'contract_uuid' => $contract->uuid,
                'user_id' => optional($request->user())->id,
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

        return response()->json(['Payment_url' => $paymentData['redirect_url']]);
    }

    public function updateCartByIPN(Request $requestData, $uuid)
    {
        try {
            $data = $requestData->all();
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

    private function calculateCartAmount(Contract $contract): float
    {
        $servicesTotal = ServicesPricing::where('contract_type', $contract->contract_type)->sum('price');
        $contractBaseTotal = (float) $contract->getPriceContractAttribute() + (float) $servicesTotal;
        $couponDiscount = $this->resolveCouponDiscount($contract, $contractBaseTotal);
        $netContractTotal = max(0, $contractBaseTotal - $couponDiscount);

        $contractPeriodPrice = (float) ContractPeriod::where('contract_type', $contract->contract_type)
            ->where('id', $contract->contract_term_in_years)
            ->firstOrFail()
            ->price;

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

    private function buildClickPayPayload(Contract $contract, float $cartAmount): array
    {
        return [
            'profile_id' => env('CLICKPAY_PROFILE_ID', '45644'),
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'cart_id' => $contract->uuid . '-' . now()->timestamp,
            'cart_description' => 'Contract ' . $contract->uuid,
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
            rtrim(env('CLICKPAY_BASE_URL', 'https://secure.clickpay.com.sa'), '/') . '/payment/request',
            $requestData
        );
    }

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

        if (!empty($paymentData['message']) && is_string($paymentData['message'])) {
            return $paymentData['message'];
        }

        if (!empty($paymentData['payment_result']['response_message']) && is_string($paymentData['payment_result']['response_message'])) {
            return $paymentData['payment_result']['response_message'];
        }

        if (!empty($paymentData['detail']) && is_string($paymentData['detail'])) {
            return $paymentData['detail'];
        }

        return 'Payment gateway did not return redirect_url.';
    }

    public function sendSmsMessage($body, $recipients, $sender, $smsId)
    {
        $bearer = '5ed5a6f23fb215fa7c1a38ec12f58491';
        $taqnyt = new TaqnyatSms($bearer);

        try {
            $message = $taqnyt->sendMsg($body, $recipients, $sender, $smsId);
            return $message ? true : false;
        }

        catch (\Exception $e) {
            return 'SMS Error: ' . $e->getMessage();
        }
    }

    private function formatPhoneNumber($mobile)
    {
        $mobile = (string) $mobile;

        $formattedNumber = preg_replace('/^0|\+/', '', $mobile);

        if (! str_starts_with($formattedNumber, '00966')) {
            $formattedNumber = '00966' . $formattedNumber;
        }

        return $formattedNumber;
    }
}