<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use Responser;

    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        try {
            $query = Role::with(['permissions', 'employees']);

            // Filter by is_active if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('title_ar', 'like', "%{$search}%")
                      ->orWhere('title_en', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $roles = $query->paginate($request->get('per_page', 20));

            return $this->apiResponse(
                [
                    'items' => $roles->items(),
                    'pagination' => $this->paginate($roles),
                ],
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        try {
            $data = [
                'validation_rules' => [
                    'name' => 'required|string|max:255|unique:roles,name',
                    'title_ar' => 'required|string|max:255',
                    'title_en' => 'nullable|string|max:255',
                    'description' => 'nullable|string',
                    'is_active' => 'nullable|boolean',
                    'permissions' => 'nullable|array',
                    'permissions.*' => 'exists:permissions,id',
                ],
                'permission_actions' => [
                    ['value' => 'view', 'label_ar' => 'عرض القسم', 'label_en' => 'View'],
                    ['value' => 'create', 'label_ar' => 'إنشاء', 'label_en' => 'Create'],
                    ['value' => 'edit', 'label_ar' => 'تعديل', 'label_en' => 'Edit'],
                    ['value' => 'delete', 'label_ar' => 'حذف', 'label_en' => 'Delete'],
                    ['value' => 'retrieve', 'label_ar' => 'استرجاع', 'label_en' => 'Retrieve'],
                ],
            ];

            return $this->apiResponse(
                $data,
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'title_ar' => 'required|string|max:255',
                'title_en' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $permissions = $validated['permissions'] ?? [];
            unset($validated['permissions']);

            $role = Role::create($validated);

            // Attach permissions if provided
            if (!empty($permissions)) {
                $role->permissions()->attach($permissions);
            }

            return $this->apiResponse(
                $role->load(['permissions']),
                trans('api.created_successfully'),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified role
     */
    public function show($id)
    {
        try {
            $role = Role::with(['permissions', 'employees'])->find($id);

            if (!$role) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            return $this->apiResponse(
                $role,
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
                'title_ar' => 'sometimes|required|string|max:255',
                'title_en' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $permissions = $validated['permissions'] ?? null;
            unset($validated['permissions']);

            $role->update($validated);

            // Sync permissions if provided
            if ($permissions !== null) {
                $role->permissions()->sync($permissions);
            }

            return $this->apiResponse(
                $role->fresh(['permissions']),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            // Check if role has employees assigned
            if ($role->employees()->count() > 0) {
                return $this->errorMessage(
                    trans('api.cannot_delete_role_with_employees'),
                    400
                );
            }

            $role->delete();

            return $this->apiResponse(
                [],
                trans('api.deleted_successfully')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $role->permissions()->sync($validated['permissions']);

            return $this->apiResponse(
                $role->fresh(['permissions']),
                trans('api.permissions_assigned_successfully')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }
}
