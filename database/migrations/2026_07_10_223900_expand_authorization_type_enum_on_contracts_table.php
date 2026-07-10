<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasColumn('contracts', 'authorization_type')) {
            return;
        }

        DB::statement("ALTER TABLE `contracts` MODIFY `authorization_type` ENUM(
            'owner_and_representative_of_record',
            'agent_for_the_tenant',
            'agent_or_authorized_by_registry_owner'
        ) NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasColumn('contracts', 'authorization_type')) {
            return;
        }

        DB::table('contracts')
            ->where('authorization_type', 'agent_or_authorized_by_registry_owner')
            ->update(['authorization_type' => 'agent_for_the_tenant']);

        DB::statement("ALTER TABLE `contracts` MODIFY `authorization_type` ENUM(
            'owner_and_representative_of_record',
            'agent_for_the_tenant'
        ) NULL");
    }
};
