<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('refundable_contracts')
            ->where('admin_confirmed', false)
            ->where('is_refunded', false)
            ->update(['admin_confirmed' => null]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE refundable_contracts MODIFY admin_confirmed TINYINT(1) NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        DB::table('refundable_contracts')
            ->whereNull('admin_confirmed')
            ->update(['admin_confirmed' => false]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE refundable_contracts MODIFY admin_confirmed TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
};
