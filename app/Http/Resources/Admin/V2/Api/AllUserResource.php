<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllUserResource extends JsonResource
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
            'full_name' => $this->name,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'phone' => $this->mobile,
            'verified' => $this->isVerified(),
            'status' => $this->is_active == 1,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'date_time' => $this->created_at_label,
            'real_estate_count' => $this->realEstate->count(),
            'properties_count' => $this->realEstate->count(),
            'units_count' => $this->unitReal->count(),
            'completed_orders_count' => $this->contracts->where('is_completed', 1)->count(),
            'uncompleted_orders_count' => $this->contracts->where('is_completed', 0)->count(),
            'incomplete_orders_count' => $this->contracts->where('is_completed', 0)->count(),
            'total_paid_amount' => round((float) ($this->total_paid_amount ?? 0), 2),
            // 'total_payment' => $this->payments->sum('amount'),
            'photo_path' => $this->photo_path ? url("storage/{$this->photo_path}") : null,
        ];
    }
}
