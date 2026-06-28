<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('real_units')) {
            return;
        }

        Schema::table('real_units', function (Blueprint $table) {
            if (! Schema::hasColumn('real_units', 'tootal_rooms')) {
                $table->string('tootal_rooms')->nullable();
            }

            if (! Schema::hasColumn('real_units', 'The_number_of_toilets')) {
                $table->string('The_number_of_toilets')->nullable();
            }

            if (! Schema::hasColumn('real_units', 'window_ac')) {
                $table->integer('window_ac')->nullable();
            }

            if (! Schema::hasColumn('real_units', 'split_ac')) {
                $table->integer('split_ac')->nullable();
            }
        });

        if (Schema::hasColumn('real_units', 'number_of_rooms') && Schema::hasColumn('real_units', 'tootal_rooms')) {
            DB::table('real_units')
                ->whereNull('tootal_rooms')
                ->whereNotNull('number_of_rooms')
                ->update(['tootal_rooms' => DB::raw('number_of_rooms')]);
        }

        if (Schema::hasColumn('real_units', 'The_number_of_the_toilet') && Schema::hasColumn('real_units', 'The_number_of_toilets')) {
            DB::table('real_units')
                ->whereNull('The_number_of_toilets')
                ->whereNotNull('The_number_of_the_toilet')
                ->update(['The_number_of_toilets' => DB::raw('The_number_of_the_toilet')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('real_units')) {
            return;
        }

        Schema::table('real_units', function (Blueprint $table) {
            foreach (['split_ac', 'window_ac', 'The_number_of_toilets', 'tootal_rooms'] as $column) {
                if (Schema::hasColumn('real_units', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
