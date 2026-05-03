<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PaymentDataAdminResource;
use App\Http\Traits\Responser;
use App\Models\Payment;
use Illuminate\Http\Request;
 
class PaymentController extends Controller
{
    use Responser;

  

    public function index(Request $request)
    {
        $query = Payment::with(['contract.user']);

        
        $query = $this->filterPayments($query, $request);

        $payments = $query->latest('payment_date')
            ->paginate($request->get('per_page', 20));

        return $this->apiResponse(
            PaymentDataAdminResource::collection($payments),
            trans('api.success')
        );
    }

    // today, week, month, year
    protected function filterPayments($query, Request $request)
    {
        $filter = $request->get('filter');

        if ($filter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($filter === 'week') {
            $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($filter === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        return $query;
    }

    /**
     * Get single payment by ID
     */
    public function show($id)
    {
        $payment = Payment::with(['contract.user'])->find($id);

        if (!$payment) {
            return $this->errorMessage(trans('api.payment_not_found') ?? 'Payment not found.', 404);
        }

        return $this->apiResponse(
            new PaymentDataAdminResource($payment),
            trans('api.success')
        );
    }
}
