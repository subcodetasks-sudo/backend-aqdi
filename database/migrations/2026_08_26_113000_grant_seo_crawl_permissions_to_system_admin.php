<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\Admin\RolePermissionResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $resolver = app(RolePermissionResolver::class);
        $resolver->syncAllPermissionsFromConfig();

        $permissionIds = Permission::query()
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        if ($permissionIds === []) {
            return;
        }

        $roleNames = array_map('strtolower', (array) config('permissions.full_access_roles', ['admin']));

        Role::query()
            ->where(function ($query) use ($roleNames) {
                $query->whereIn('name', $roleNames)
                    ->orWhere('title_en', 'System Admin')
                    ->orWhere('title_ar', 'مدير النظام');
            })
            ->get()
            ->each(function (Role $role) use ($permissionIds) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            });
    }

    public function down(): void
    {
        // Permissions stay; detaching from admin would drop unrelated grants.
    }
};
