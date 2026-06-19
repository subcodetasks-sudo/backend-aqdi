<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_paid_by_employees')
            || ! Schema::hasColumn('contract_paid_by_employees', 'contract_uuid')) {
            return;
        }

        DB::statement('ALTER TABLE `contract_paid_by_employees` MODIFY `contract_uuid` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('contract_paid_by_employees')
            || ! Schema::hasColumn('contract_paid_by_employees', 'contract_uuid')) {
            return;
        }

        DB::statement('ALTER TABLE `contract_paid_by_employees` MODIFY `contract_uuid` VARCHAR(255) NOT NULL');
    }
};
