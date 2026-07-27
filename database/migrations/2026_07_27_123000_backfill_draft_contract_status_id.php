<?php

use App\Models\DraftContractStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasColumn('contracts', 'draft_contract_status_id')) {
            return;
        }

        $newId = DraftContractStatus::query()->where('name', DraftContractStatus::NEW_NAME)->value('id');
        if (! $newId) {
            $newId = DraftContractStatus::query()->orderBy('id')->value('id');
        }

        if (! $newId) {
            return;
        }

        DB::table('contracts')
            ->where('is_draft', 1)
            ->whereNull('draft_contract_status_id')
            ->update([
                'draft_contract_status_id' => (int) $newId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Non-reversible data backfill.
    }
};
