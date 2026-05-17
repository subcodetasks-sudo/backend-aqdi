<?php

namespace Database\Seeders;

use App\Services\Admin\RolePermissionResolver;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $created = app(RolePermissionResolver::class)->syncAllPermissionsFromConfig();

        $this->command?->info("Permissions synced. Created: {$created}");
    }
}
