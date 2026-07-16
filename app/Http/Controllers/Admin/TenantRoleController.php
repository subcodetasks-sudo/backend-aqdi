<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\TenantRoleResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\TenantRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TenantRoleController extends Controller
{
    use Responser;

    /**
     * GET /api/admin/tenant-roles
     */
    public function index(Request $request)
    {
        try {
            $query = TenantRole::query();

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where('text_of_reason', 'like', '%'.$search.'%');
            }

            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $allowedSort = ['id', 'text_of_reason', 'created_at', 'updated_at'];
            if (! in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'id';
            }

            $records = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $records,
                TenantRoleResource::collection($records),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/tenant-roles/{id}
     */
    public function show(int $id)
    {
        try {
            $record = TenantRole::query()->find($id);

            if (! $record) {
                return $this->errorMessage(trans('api.tenant_role_not_found'), 404);
            }

            return $this->apiResponse(
                new TenantRoleResource($record),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/tenant-roles
     */
    public function store(Request $request)
    {
        try {
            $validator = $this->validateTenantRole($request);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $record = TenantRole::query()->create([
                'text_of_reason' => trim((string) $request->input('text_of_reason')),
            ]);

            return $this->apiResponse(
                new TenantRoleResource($record),
                trans('api.created_successfully'),
                201
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/tenant-roles/{id}
     */
    public function update(Request $request, int $id)
    {
        try {
            $record = TenantRole::query()->find($id);

            if (! $record) {
                return $this->errorMessage(trans('api.tenant_role_not_found'), 404);
            }

            $validator = $this->validateTenantRole($request);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $record->update([
                'text_of_reason' => trim((string) $request->input('text_of_reason')),
            ]);

            return $this->apiResponse(
                new TenantRoleResource($record->fresh()),
                trans('api.updated_successfully')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/tenant-roles/{id}/delete
     */
    public function destroy(int $id)
    {
        try {
            $record = TenantRole::query()->find($id);

            if (! $record) {
                return $this->errorMessage(trans('api.tenant_role_not_found'), 404);
            }

            if ($this->tenantRoleIsInUse($id)) {
                return $this->errorMessage(trans('api.tenant_role_in_use'), 422);
            }

            $record->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function validateTenantRole(Request $request)
    {
        return Validator::make($request->all(), [
            'text_of_reason' => ['required', 'string', 'max:500'],
        ], [], [
            'text_of_reason' => 'صفة المستأجر',
        ]);
    }

    private function tenantRoleIsInUse(int $id): bool
    {
        if (Contract::query()->where('tenant_role_id', $id)->exists()) {
            return true;
        }

        return Contract::query()
            ->whereNotNull('tenant_role_ids')
            ->where('tenant_role_ids', 'like', '%"'.$id.'"%')
            ->exists();
    }
}
