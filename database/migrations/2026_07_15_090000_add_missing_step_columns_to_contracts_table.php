<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        // ملف وكالة النظار للوكيل (يُرسل في خطوة الصك عند نوع صك الوقف)
        if (! Schema::hasColumn('contracts', 'copy_of_guardians_power_of_attorney_for_agent')) {
            DB::statement('ALTER TABLE `contracts` ADD COLUMN `copy_of_guardians_power_of_attorney_for_agent` TEXT NULL');
        }

        // خطوة الوحدة V2 تكتب The_number_of_toilets؛ العمود القديم The_number_of_the_toilet
        if (! Schema::hasColumn('contracts', 'The_number_of_toilets')) {
            DB::statement('ALTER TABLE `contracts` ADD COLUMN `The_number_of_toilets` VARCHAR(255) NULL');

            if (Schema::hasColumn('contracts', 'The_number_of_the_toilet')) {
                DB::table('contracts')
                    ->whereNull('The_number_of_toilets')
                    ->whereNotNull('The_number_of_the_toilet')
                    ->update(['The_number_of_toilets' => DB::raw('`The_number_of_the_toilet`')]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        foreach (['copy_of_guardians_power_of_attorney_for_agent', 'The_number_of_toilets'] as $column) {
            if (Schema::hasColumn('contracts', $column)) {
                DB::statement("ALTER TABLE `contracts` DROP COLUMN `{$column}`");
            }
        }
    }
};
