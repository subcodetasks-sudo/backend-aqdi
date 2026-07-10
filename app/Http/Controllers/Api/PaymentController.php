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
        return $this->paymentService->createPaymentUrlResponse(
            (string) $uuid,
            $this->resolvePaymentClient($request)
        );
    }

    /**
     * Resolve whether the payment link is for web or the mobile app.
     * Web keeps frontend redirects; app skips them (or uses app deep-link templates).
     */
    private function resolvePaymentClient(Request $request): string
    {
        $client = strtolower((string) (
            $request->query('platform')
            ?? $request->query('client')
            ?? $request->header('X-Client')
            ?? $request->header('X-Platform')
            ?? 'web'
        ));

        return in_array($client, ['app', 'mobile', 'ios', 'android'], true) ? 'app' : 'web';
    }

    public function updateCartByIPN(Request $requestData, $uuid)
    {
        $this->paymentService->processIpn($requestData, (string) $uuid);
    }

    public function Callback(Request $request, $uuid)
    {
        $this->paymentService->processIpn($request, (string) $uuid);

        return response()->json(
            $this->paymentService->paymentStatusPayload(
                (string) $uuid,
                'return',
                $request->input('id') ?? $request->input('payment_id'),
                $request->input('invoice_id')
            )
        );
    }

    public function success(Request $request, $uuid)
    {
        $this->paymentService->processIpn($request, (string) $uuid);

        return $this->apiResponse(
            $this->paymentService->paymentStatusPayload(
                (string) $uuid,
                'success',
                $request->input('id') ?? $request->input('payment_id'),
                $request->input('invoice_id')
            ),
            trans('api.success')
        );
    }

    public function error(Request $request, $uuid)
    {
        $this->paymentService->processIpn($request, (string) $uuid);

        return $this->apiResponse(
            $this->paymentService->paymentStatusPayload(
                (string) $uuid,
                'error',
                $request->input('id') ?? $request->input('payment_id'),
                $request->input('invoice_id')
            ),
            trans('api.error'),
            400
        );
    }

    public function syncFromGateway(Request $request, string $uuid)
    {
        return $this->apiResponse(
            $this->paymentService->syncGatewayPaymentStatus(
                (string) $uuid,
                $request->input('id') ?? $request->input('payment_id'),
                $request->input('invoice_id')
            ),
            trans('api.success')
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
