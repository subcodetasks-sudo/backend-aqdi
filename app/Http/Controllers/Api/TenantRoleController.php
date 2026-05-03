<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\TenantRole;

class TenantRoleController extends Controller
{
    use Responser;

    public function index()
    {
        $roles = TenantRole::query()->get();

        return $this->apiResponse($roles, trans('api.roles'));
    }
}