<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fname' => $this->fname,
            'full_name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'photo' => $this->photo_path,
            'verified' => $this->isVerified(),
            'name' => $this->name,
            'phone' => $this->mobile,
            'status' => $this->is_active == 1,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'date_time' => $this->created_at_label,
            'properties_count' => $this->realEstate->count(),
            'units_count' => $this->unitReal->count(),
            'completed_orders_count' => $this->contracts->where('is_completed', 1)->count(),
            'incomplete_orders_count' => $this->contracts->where('is_completed', 0)->count(),
            'total_paid_amount' => round((float) DB::table('payments')
                ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
                ->where('contracts.user_id', $this->id)
                ->where('payments.status', 'success')
                ->sum('payments.amount'), 2),
        ];
    }
}
