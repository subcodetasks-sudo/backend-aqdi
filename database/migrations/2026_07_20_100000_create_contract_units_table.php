<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_units')) {
            Schema::create('contract_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_id');
                $table->unsignedBigInteger('real_unit_id');
                $table->unsignedBigInteger('real_estate_id')->nullable();
                $table->timestamps();

                $table->unique(['contract_id', 'real_unit_id']);
                $table->index('real_estate_id');
            });

            // Add FKs after create so we can control order / skip bad data on backfill first.
            Schema::table('contract_units', function (Blueprint $table) {
                $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
                $table->foreign('real_unit_id')->references('id')->on('real_units')->cascadeOnDelete();
                $table->foreign('real_estate_id')->references('id')->on('real_estates')->nullOnDelete();
            });
        }

        $this->backfillFromLegacyContracts();
    }

    private function backfillFromLegacyContracts(): void
    {
        if (! Schema::hasTable('contracts')
            || ! Schema::hasColumn('contracts', 'real_units_id')
            || ! Schema::hasTable('contract_units')
            || ! Schema::hasTable('real_units')) {
            return;
        }

        // Only link rows where the unit exists. Never copy orphan real_id values.
        // real_estate_id is filled only when that real_estates row exists; otherwise NULL.
        $sql = '
            INSERT INTO contract_units (contract_id, real_unit_id, real_estate_id, created_at, updated_at)
            SELECT
                c.id,
                c.real_units_id,
                CASE
                    WHEN re.id IS NULL THEN NULL
                    ELSE c.real_id
                END AS real_estate_id,
                NOW(),
                NOW()
            FROM contracts c
            INNER JOIN real_units ru ON ru.id = c.real_units_id
            LEFT JOIN real_estates re ON re.id = c.real_id
            WHERE c.real_units_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM contract_units cu
                  WHERE cu.contract_id = c.id
                    AND cu.real_unit_id = c.real_units_id
              )
        ';

        DB::statement($sql);
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_units');
    }
};
