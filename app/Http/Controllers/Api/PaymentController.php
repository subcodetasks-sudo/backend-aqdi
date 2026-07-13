<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\PaymentMessageResource;
use App\Http\Traits\Responser;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\PaymentMessage;
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
     * Detect web vs mobile without requiring app changes.
     * Web: Origin/Referer matching PAYMENT_FRONTEND_URL, or platform=web.
     * Default is "app" (uses PAYMENT_APP_* deep links when set; otherwise same
     * backend→frontend success/error redirect as web).
     */
    private function resolvePaymentClient(Request $request): string
    {
        $explicit = strtolower((string) (
            $request->query('platform')
            ?? $request->query('client')
            ?? $request->header('X-Client')
            ?? $request->header('X-Platform')
            ?? ''
        ));

        if (in_array($explicit, ['app', 'mobile', 'ios', 'android'], true)) {
            return 'app';
        }

        if (in_array($explicit, ['web', 'frontend'], true)) {
            return 'web';
        }

        if ($this->requestLooksLikeWebFrontend($request)) {
            return 'web';
        }

        return 'app';
    }

    private function requestLooksLikeWebFrontend(Request $request): bool
    {
        $frontendBase = rtrim((string) config('services.moyasar.payment_frontend_url', ''), '/');
        $hosts = [];

        if ($frontendBase !== '') {
            $frontendHost = parse_url($frontendBase, PHP_URL_HOST);
            if (is_string($frontendHost) && $frontendHost !== '') {
                $hosts[] = strtolower($frontendHost);
            }
        }

        // Always treat the known AQDI frontend host as web.
        $hosts[] = 'aqdi-front-end.vercel.app';
        $hosts = array_values(array_unique($hosts));

        foreach ([$request->headers->get('Origin'), $request->headers->get('Referer')] as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);
            if (! is_string($host) || $host === '') {
                continue;
            }

            if (in_array(strtolower($host), $hosts, true)) {
                return true;
            }
        }

        return false;
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

    /**
     * Single entry from Moyasar: decide success vs failed from real payment state.
     * GET /api/status/result/{uuid}
     */
    public function result(Request $request, $uuid)
    {
        return $this->redirectByPaymentState($request, (string) $uuid);
    }

    public function success(Request $request, $uuid)
    {
        return $this->redirectByPaymentState($request, (string) $uuid);
    }

    public function error(Request $request, $uuid)
    {
        return $this->redirectByPaymentState($request, (string) $uuid);
    }

    /**
     * Public status for the frontend success/failed screens.
     * GET /api/payment/result/{uuid}
     */
    public function paymentResult(Request $request, $uuid)
    {
        $uuid = (string) $uuid;

        try {
            $this->paymentService->processIpn($request, $uuid);
        } catch (\Throwable) {
            // Best-effort; still resolve from local DB / gateway sync below.
        }

        try {
            $payload = $this->paymentService->paymentStatusPayload(
                $uuid,
                'return',
                $request->input('id') ?? $request->input('payment_id'),
                $request->input('invoice_id')
            );
        } catch (\Throwable) {
            $paid = $this->paymentService->isPaymentConfirmed($uuid);
            $type = $paid ? 'success' : 'failed';
            $message = PaymentMessage::query()->where('type', $type)->first();

            return $this->apiResponse([
                'paid' => $paid,
                'status' => $paid ? 'success' : 'failed',
                'screen' => $paid ? 'success' : 'error',
                'contract_uuid' => $uuid,
                'contract_id' => null,
                'is_completed' => false,
                'payment' => null,
                'content' => $message ? (new PaymentMessageResource($message))->resolve() : null,
                'frontend_url' => $this->frontendPaymentRedirectUrl($paid ? 'success' : 'error', $uuid),
            ], trans('api.success'));
        }

        $paid = (bool) ($payload['payment_confirmed'] ?? false);
        $type = $paid ? 'success' : 'failed';
        $message = PaymentMessage::query()->where('type', $type)->first();

        return $this->apiResponse([
            'paid' => $paid,
            'status' => $paid ? 'success' : 'failed',
            'screen' => $paid ? 'success' : 'error',
            'contract_uuid' => $uuid,
            'contract_id' => $payload['contract_id'] ?? null,
            'is_completed' => (bool) ($payload['is_completed'] ?? false),
            'payment' => $payload['payment'] ?? null,
            'content' => $message ? (new PaymentMessageResource($message))->resolve() : null,
            'frontend_url' => $this->frontendPaymentRedirectUrl($paid ? 'success' : 'error', $uuid),
        ], trans('api.success'));
    }

    private function redirectByPaymentState(Request $request, string $uuid)
    {
        try {
            $this->paymentService->processIpn($request, $uuid);
        } catch (\Throwable) {
            // Continue and resolve from stored/gateway state.
        }

        $paid = $this->paymentService->isPaymentConfirmed(
            $uuid,
            $request->input('id') ?? $request->input('payment_id'),
            $request->input('invoice_id')
        );
        $result = $paid ? 'success' : 'error';
        $frontendUrl = $this->frontendPaymentRedirectUrl($result, $uuid);

        if ($this->wantsJsonPaymentResponse($request)) {
            return $this->apiResponse(
                $this->paymentService->paymentStatusPayload(
                    $uuid,
                    $result,
                    $request->input('id') ?? $request->input('payment_id'),
                    $request->input('invoice_id')
                ),
                $paid ? trans('api.success') : trans('api.error'),
                $paid ? 200 : 400
            );
        }

        return redirect()->away($frontendUrl);
    }

    private function frontendPaymentRedirectUrl(string $type, string $uuid): string
    {
        $templateKey = $type === 'error'
            ? 'services.moyasar.payment_error_url_template'
            : 'services.moyasar.payment_success_url_template';

        $template = (string) config($templateKey, '');
        if ($template !== '') {
            $url = str_replace('{uuid}', $uuid, $template);
        } else {
            $base = rtrim((string) config('services.moyasar.payment_frontend_url', 'http://localhost:3000'), '/');
            $path = $type === 'error' ? 'error' : 'success';
            $url = "{$base}/payment/{$path}/{$uuid}";
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'status='.($type === 'error' ? 'failed' : 'success');
    }

    private function wantsJsonPaymentResponse(Request $request): bool
    {
        // Only skip the frontend redirect when the client explicitly asks for JSON.
        return $request->query('format') === 'json';
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
