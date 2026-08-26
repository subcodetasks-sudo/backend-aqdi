<?php

use App\Services\Admin\RolePermissionResolver;
use App\Support\Migrations\SeoCrawlTables;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SeoCrawlTables::ensure();
        app(RolePermissionResolver::class)->grantAllPermissionsToFullAccessRoles();
    }

    public function down(): void
    {
        // Keep catalog rows, grants, and widened crawl columns.
    }
};
