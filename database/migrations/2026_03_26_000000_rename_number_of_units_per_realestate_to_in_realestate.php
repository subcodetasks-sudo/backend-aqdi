<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * For databases that already ran the older migration ending in ..._per_realestate.
     */
    public function up(): void
    {
        if (Schema::hasColumn('real_estates', 'number_of_units_per_realestate')) {
            DB::statement('ALTER TABLE `real_estates` CHANGE `number_of_units_per_realestate` `number_of_units_in_realestate` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('contracts', 'number_of_units_per_realestate')) {
            DB::statement('ALTER TABLE `contracts` CHANGE `number_of_units_per_realestate` `number_of_units_in_realestate` VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('real_estates', 'number_of_units_in_realestate')) {
            DB::statement('ALTER TABLE `real_estates` CHANGE `number_of_units_in_realestate` `number_of_units_per_realestate` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('contracts', 'number_of_units_in_realestate')) {
            DB::statement('ALTER TABLE `contracts` CHANGE `number_of_units_in_realestate` `number_of_units_per_realestate` VARCHAR(255) NULL');
        }
    }
};
