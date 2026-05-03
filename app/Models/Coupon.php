<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $fillable=['name', 'code_coupon', 'type_coupon', 'is_delete','value_coupon', 'date_start', 'date_end','usage','status','usage_of_user'];



    public static function createUsage($userId, $couponId, $contractUuid)
    {
        return self::create([
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'contract_uuid' => $contractUuid,
            'used_at' => now(),
        ]);
    }
   /*
    |--------------------------------------------------------------------------
    | Scope Contract Review
    |--------------------------------------------------------------------------
    */


    public function scopeValid($query)
    {
        return $query->where('date_start', '<=', now())
                     ->where('date_end', '>=', now());
    }

    public function scopeUserUsage($query, $userId, $couponId)
    {
        return $query->where('user_id', $userId)
                     ->where('coupon_id', $couponId);
    }
    
    public function scopeUserContractUsage($query, $userId, $couponId, $contractUuid)
    {
        return $query->where('user_id', $userId)
                     ->where('coupon_id', $couponId)
                     ->where('contract_uuid', $contractUuid);
    }
    
    public function scopeHasRemainingUsage($query)
    {
        return $query->where('usage', '>', 0);
    }


    public function scopdecrementUsage()
    {
        $this->decrement('usage');
    }

 


}
