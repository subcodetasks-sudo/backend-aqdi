<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'other_conditions_list')) {
                $table->json('other_conditions_list')->nullable()->after('other_conditions');
            }
        });

        // Backfill: single other_conditions text → list of one.
        if (Schema::hasColumn('contracts', 'other_conditions')
            && Schema::hasColumn('contracts', 'other_conditions_list')) {
            DB::table('contracts')
                ->whereNotNull('other_conditions')
                ->where('other_conditions', '!=', '')
                ->whereNull('other_conditions_list')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $text = trim((string) $row->other_conditions);
                        if ($text === '') {
                            continue;
                        }
                        DB::table('contracts')->where('id', $row->id)->update([
                            'other_conditions_list' => json_encode([$text], JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'other_conditions_list')) {
                $table->dropColumn('other_conditions_list');
            }
        });
    }
};
