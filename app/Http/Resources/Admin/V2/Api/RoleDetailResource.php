<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(
            (new RoleResource($this))->toArray($request),
            [
                'permissions' => PermissionResource::collection(
                    $this->whenLoaded('permissions')
                ),
                'permission_ids' => $this->when(
                    $this->relationLoaded('permissions'),
                    fn () => $this->permissions->pluck('id')->values()->all()
                ),
            ]
        );
    }
}
