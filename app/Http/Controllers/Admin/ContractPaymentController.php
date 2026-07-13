<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PaymentDataAdminResource;
use App\Http\Traits\Responser;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Http\Request;

class ContractPaymentController extends Controller
{
    use Responser;

    public function __construct(
        protected PaymentGatewayInterface $paymentService
    ) {}

    /**
     * Get ClickPay redirect URL for a contract.
     * GET /api/admin/payment-gateway/{uuid}
     */
    public function paymentUrl(string $uuid)
    {
        return $this->paymentService->createPaymentUrlResponse($uuid);
    }

    /**
     * List payment records for a contract uuid.
     * GET /api/admin/payment-gateway/{uuid}/payments
     */
    public function paymentsByContract(string $uuid)
    {
        $payments = Payment::query()
            ->with(['contract.user'])
            ->matchingContractUuid($uuid)
            ->latest('id')
            ->get();

        return $this->apiResponse(
            PaymentDataAdminResource::collection($payments),
            trans('api.success')
        );
    }

    /**
     * ClickPay IPN callback.
     * POST /api/admin/payment-gateway/status/{uuid}/success
     */
    public function updateCartByIPN(Request $request, string $uuid)
    {
        $this->paymentService->processIpn($request, $uuid);
    }

    /**
     * ClickPay return callback.
     * POST /api/admin/payment-gateway/status/{uuid}
     */
    public function callback(Request $request, string $uuid)
    {
        $this->paymentService->processIpn($request, $uuid);

        return response()->json(
            $this->paymentService->paymentStatusPayload(
                $uuid,
                'return',
                $request->input('id') ?? $request->input('payment_id'),
                $request->input('invoice_id')
            )
        );
    }

    /**
     * GET /api/admin/payment-gateway/status/success/{uuid}
     */
    public function success(Request $request, string $uuid)
    {
        $this->paymentService->processIpn($request, $uuid);

        return redirect()->away($this->frontendPaymentRedirectUrl('success', $uuid));
    }

    /**
     * GET /api/admin/payment-gateway/status/error/{uuid}
     */
    public function error(Request $request, string $uuid)
    {
        $this->paymentService->processIpn($request, $uuid);

        return redirect()->away($this->frontendPaymentRedirectUrl('error', $uuid));
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
}
