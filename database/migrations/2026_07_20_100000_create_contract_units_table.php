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
                $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
                $table->foreignId('real_unit_id')->constrained('real_units')->cascadeOnDelete();
                $table->foreignId('real_estate_id')->nullable()->constrained('real_estates')->nullOnDelete();
                $table->timestamps();

                $table->unique(['contract_id', 'real_unit_id']);
                $table->index('real_estate_id');
            });
        }

        // Backfill from legacy contracts.real_units_id (skip orphan FKs)
        if (! Schema::hasTable('contracts')
            || ! Schema::hasColumn('contracts', 'real_units_id')
            || ! Schema::hasTable('contract_units')
            || ! Schema::hasTable('real_units')) {
            return;
        }

        $hasRealEstates = Schema::hasTable('real_estates');

        $rows = DB::table('contracts')
            ->whereNotNull('real_units_id')
            ->select(['id', 'real_units_id', 'real_id'])
            ->get();

        foreach ($rows as $row) {
            $unitExists = DB::table('real_units')->where('id', $row->real_units_id)->exists();
            if (! $unitExists) {
                continue;
            }

            $alreadyLinked = DB::table('contract_units')
                ->where('contract_id', $row->id)
                ->where('real_unit_id', $row->real_units_id)
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            $realEstateId = null;
            if ($hasRealEstates && $row->real_id) {
                $realEstateId = DB::table('real_estates')->where('id', $row->real_id)->exists()
                    ? $row->real_id
                    : null;
            }

            DB::table('contract_units')->insert([
                'contract_id' => $row->id,
                'real_unit_id' => $row->real_units_id,
                'real_estate_id' => $realEstateId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_units');
    }
};
