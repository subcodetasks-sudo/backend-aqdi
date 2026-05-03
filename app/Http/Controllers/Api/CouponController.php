<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    use Responser;

    public function Coupon(Request $request, $uuid)
    {
        // Validate the incoming request
        $request->validate([
            'code_coupon' => 'required',
        ]);

        try {
            // Retrieve the contract based on UUID
            $contract = Contract::where('uuid', $uuid)->firstOrFail();

            // Check if the coupon exists and is valid
            $contract_coupon = Coupon::where('is_delete', 0)
                ->where('code_coupon', $request->code_coupon)
                ->where('date_start', '<=', now())
                ->where('date_end', '>=', now())
                ->first();

            if (!$contract_coupon) {
                return $this->errorMessage('الكود غير صحيح', 200);
            }

            $user = Auth::user();
            $usage_limit = $contract_coupon->usage_of_user;

            // Check the coupon usage limits
            if ($contract_coupon->usage > 0) {
                $userCouponUsageCount = CouponUsage::where('user_id', $user->id)
                    ->where('coupon_id', $contract_coupon->id)
                    ->count();

                $userContractCouponUsageCount = CouponUsage::where('user_id', $user->id)
                    ->where('coupon_id', $contract_coupon->id)
                    ->where('contract_uuid', $contract->uuid)
                    ->count();

                if ($userCouponUsageCount >= $usage_limit) {
                    return $this->errorMessage('تم تجاوز حد استخدام الكوبون', 200);
                }

                if ($userContractCouponUsageCount > 0) {
                    return $this->errorMessage('لقد استخدمت هذا الكوبون لهذا العقد بالفعل', 200);
                }

                // Decrement the usage count and log the usage
                $contract_coupon->decrement('usage');

                CouponUsage::create([
                    'user_id' => $user->id,
                    'coupon_id' => $contract_coupon->id,
                    'contract_uuid' => $contract->uuid,
                    'used_at' => now(),
                ]);

               if ($contract_coupon->type_coupon == 'ratio') {
                return $this->successMessage([
                     'status' => 'success',
                    'message' => 'تم خصم ' . $contract_coupon->value_coupon . '% من قيمة العقد بنجاح',
                    'data' => $contract_coupon->value_coupon
                ], 200);
            } else {
                return $this->successMessage([
                     'status' => 'success',
                    'message' => 'تم خصم ' . $contract_coupon->value_coupon . ' ريال بنجاح',
                    'data' => $contract_coupon->value_coupon
                ], 200);
            }

            } else {
                return $this->errorMessage('تم تجاوز حد استخدام الكوبون', 200);
            }
        } catch (\Exception $e) {
            Log::error('Error applying coupon: ' . $e->getMessage() . ' Contract UUID: ' . $uuid);
            return $this->errorMessage('حدث خطأ أثناء تطبيق الكوبون. الرجاء المحاولة مرة أخرى.', 500);
        }
    }
}
