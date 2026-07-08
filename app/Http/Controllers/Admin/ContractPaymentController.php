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
            $this->paymentService->paymentStatusPayload($uuid, 'return')
        );
    }

    /**
     * GET /api/admin/payment-gateway/status/success/{uuid}
     */
    public function success(string $uuid)
    {
        return $this->apiResponse(
            $this->paymentService->paymentStatusPayload($uuid, 'success'),
            trans('api.success')
        );
    }

    /**
     * GET /api/admin/payment-gateway/status/error/{uuid}
     */
    public function error(string $uuid)
    {
        return $this->apiResponse(
            $this->paymentService->paymentStatusPayload($uuid, 'error'),
            trans('api.error'),
            400
        );
    }
}
