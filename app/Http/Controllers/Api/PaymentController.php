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
     * Detect web vs mobile without requiring app changes.
     * Default is "app" (no frontend redirect). Web is detected via
     * Origin/Referer matching PAYMENT_FRONTEND_URL, or an explicit platform=web.
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

    public function success(Request $request, $uuid)
    {
        $this->paymentService->processIpn($request, (string) $uuid);

        $frontendUrl = $this->frontendPaymentRedirectUrl('success', (string) $uuid);

        if ($this->wantsJsonPaymentResponse($request)) {
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

        return redirect()->away($frontendUrl);
    }

    public function error(Request $request, $uuid)
    {
        $this->paymentService->processIpn($request, (string) $uuid);

        $frontendUrl = $this->frontendPaymentRedirectUrl('error', (string) $uuid);

        if ($this->wantsJsonPaymentResponse($request)) {
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

        return redirect()->away($frontendUrl);
    }

    private function frontendPaymentRedirectUrl(string $type, string $uuid): string
    {
        $templateKey = $type === 'error'
            ? 'services.moyasar.payment_error_url_template'
            : 'services.moyasar.payment_success_url_template';

        $template = (string) config($templateKey, '');
        if ($template !== '') {
            return str_replace('{uuid}', $uuid, $template);
        }

        $base = rtrim((string) config('services.moyasar.payment_frontend_url', 'http://localhost:3000'), '/');
        $path = $type === 'error' ? 'error' : 'success';

        return "{$base}/payment/{$path}/{$uuid}";
    }

    private function wantsJsonPaymentResponse(Request $request): bool
    {
        if ($request->query('format') === 'json') {
            return true;
        }

        return $request->expectsJson() && ! $request->acceptsHtml();
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
