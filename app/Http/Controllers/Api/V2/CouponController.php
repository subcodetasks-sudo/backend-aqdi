<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CouponController extends \App\Http\Controllers\Api\CouponController
{
    use Responser;

    public function Coupon(Request $request, $uuid)
    {
        $request->validate([
            'code_coupon' => 'required',
        ]);

        try {
            $contract = Contract::where('uuid', $uuid)->firstOrFail();

            $coupon = Coupon::where('is_delete', 0)
                ->where('code_coupon', $request->code_coupon)
                ->where('date_start', '<=', now())
                ->where('date_end', '>=', now())
                ->first();

            if (! $coupon) {
                return $this->errorMessage('الكود غير صحيح', 200);
            }

            $user = Auth::user();
            $usageLimit = $coupon->usage_of_user;

            if ($coupon->usage <= 0) {
                return $this->errorMessage('تم تجاوز حد استخدام الكوبون', 200);
            }

            $userCouponUsageCount = CouponUsage::where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->count();

            $userContractCouponUsageCount = CouponUsage::where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->where('contract_uuid', $contract->uuid)
                ->count();

            if ($userCouponUsageCount >= $usageLimit) {
                return $this->errorMessage('تم تجاوز حد استخدام الكوبون', 200);
            }

            if ($userContractCouponUsageCount > 0) {
                return $this->errorMessage('لقد استخدمت هذا الكوبون لهذا العقد بالفعل', 200);
            }

            $totalBefore = (float) $contract->getPriceContractAttribute();
            $discount = $coupon->type_coupon === 'ratio'
                ? ($totalBefore * (float) $coupon->value_coupon / 100)
                : (float) $coupon->value_coupon;
            $discount = min($discount, $totalBefore);
            $totalAfter = $totalBefore - $discount;

            $coupon->decrement('usage');

            CouponUsage::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'contract_uuid' => $contract->uuid,
                'used_at' => now(),
            ]);

            $message = $coupon->type_coupon === 'ratio'
                ? 'تم خصم ' . $coupon->value_coupon . '% من قيمة العقد بنجاح'
                : 'تم خصم ' . $coupon->value_coupon . ' ريال بنجاح';

            return $this->apiResponse([
                'type_coupon' => $coupon->type_coupon,
                'value_coupon' => (float) $coupon->value_coupon,
                'discount' => round($discount, 2),
                'total_price_before_coupon' => round($totalBefore, 2),
                'total_price_after_coupon' => round($totalAfter, 2),
            ], $message, 200);
        } catch (\Exception $e) {
            Log::error('Error applying coupon: ' . $e->getMessage() . ' Contract UUID: ' . $uuid);

            return $this->errorMessage('حدث خطأ أثناء تطبيق الكوبون. الرجاء المحاولة مرة أخرى.', 500);
        }
    }
}
