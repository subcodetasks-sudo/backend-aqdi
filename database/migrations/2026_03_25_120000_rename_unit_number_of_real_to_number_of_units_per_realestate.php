<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('real_estates', 'unit_number_of_real')) {
            DB::statement('ALTER TABLE `real_estates` CHANGE `unit_number_of_real` `number_of_units_in_realestate` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('contracts', 'unit_number_of_real')) {
            DB::statement('ALTER TABLE `contracts` CHANGE `unit_number_of_real` `number_of_units_in_realestate` VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('real_estates', 'number_of_units_in_realestate')) {
            DB::statement('ALTER TABLE `real_estates` CHANGE `number_of_units_in_realestate` `unit_number_of_real` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('contracts', 'number_of_units_in_realestate')) {
            DB::statement('ALTER TABLE `contracts` CHANGE `number_of_units_in_realestate` `unit_number_of_real` VARCHAR(255) NULL');
        }
    }
};
