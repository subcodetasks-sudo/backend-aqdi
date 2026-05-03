<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        ];
    }
}
