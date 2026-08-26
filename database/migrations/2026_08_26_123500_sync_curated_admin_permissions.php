<?php

use App\Services\Admin\RolePermissionResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RolePermissionResolver::class)->grantAllPermissionsToFullAccessRoles();
    }

    public function down(): void
    {
        // Keep catalog rows and grants because they may predate this migration.
    }
};
