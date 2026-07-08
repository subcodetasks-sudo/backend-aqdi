<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use TaqnyatSms;

class PaymentController extends Controller
{
    use Responser;

    public function __construct(
        protected PaymentGatewayInterface $paymentService
    ) {}

    public function index(Request $request, $uuid)
    {
        return $this->paymentService->createPaymentUrlResponse((string) $uuid);
    }

    public function updateCartByIPN(Request $requestData, $uuid)
    {
        $this->paymentService->processIpn($requestData, (string) $uuid);
    }

    public function Callback(Request $request, $uuid)
    {
        $this->paymentService->processIpn($request, (string) $uuid);

        return response()->json(
            $this->paymentService->paymentStatusPayload((string) $uuid, 'return')
        );
    }

    public function success($uuid)
    {
        return $this->apiResponse(
            $this->paymentService->paymentStatusPayload((string) $uuid, 'success'),
            trans('api.success')
        );
    }

    public function error($uuid)
    {
        return $this->apiResponse(
            $this->paymentService->paymentStatusPayload((string) $uuid, 'error'),
            trans('api.error'),
            400
        );
    }

    public function sendSmsMessage($body, $recipients, $sender, $smsId)
    {
        $bearer = '5ed5a6f23fb215fa7c1a38ec12f58491';
        $taqnyt = new TaqnyatSms($bearer);

        try {
            $message = $taqnyt->sendMsg($body, $recipients, $sender, $smsId);

            return $message ? true : false;
        } catch (\Exception $e) {
            return 'SMS Error: '.$e->getMessage();
        }
    }

    private function formatPhoneNumber($mobile)
    {
        $mobile = (string) $mobile;

        $formattedNumber = preg_replace('/^0|\+/', '', $mobile);

        if (! str_starts_with($formattedNumber, '00966')) {
            $formattedNumber = '00966'.$formattedNumber;
        }

        return $formattedNumber;
    }
}
