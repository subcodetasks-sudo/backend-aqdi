<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_ID = 1;

    private const RECEIVED_ID = 6;

    public function up(): void
    {
        if (! Schema::hasTable('contracts')
            || ! Schema::hasTable('contract_statuses')
            || ! Schema::hasColumn('contracts', 'contract_status_id')) {
            return;
        }

        $statusExists = fn (int $id): bool => DB::table('contract_statuses')->where('id', $id)->exists();

        // عقود بدون حالة وتم استلامها → مستلم
        if ($statusExists(self::RECEIVED_ID) && Schema::hasTable('received_contracts')) {
            DB::table('contracts')
                ->whereNull('contract_status_id')
                ->whereIn('id', DB::table('received_contracts')->select('contract_id'))
                ->update(['contract_status_id' => self::RECEIVED_ID]);
        }

        // أي عقد بدون حالة (لم يُستلم) → جديد
        if ($statusExists(self::NEW_ID)) {
            DB::table('contracts')
                ->whereNull('contract_status_id')
                ->update(['contract_status_id' => self::NEW_ID]);
        }
    }

    public function down(): void
    {
        // Data backfill — nothing to reverse safely.
    }
};
