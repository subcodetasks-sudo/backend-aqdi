<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\TenantRoleResource;
use App\Http\Traits\Responser;
use App\Models\TenantRole;

class TenantRoleController extends Controller
{
    use Responser;

    public function index()
    {
        $roles = TenantRole::query()->orderBy('id')->get();

        return $this->apiResponse(
            TenantRoleResource::collection($roles),
            trans('api.roles')
        );
    }
}
