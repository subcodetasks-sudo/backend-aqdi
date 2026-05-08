<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasColumn('contracts', 'instrument_type')) {
            return;
        }

        DB::statement("
            ALTER TABLE `contracts`
            MODIFY `instrument_type` ENUM(
                'electronic',
                'old_handwritten',
                'strong_argument',
                'electronic_tax_register',
                'property_ownership_owner_are_deceased_endowment',
                'sale_agreement',
                'electronic_deed_from_the_ministry_of_justice',
                'economic_cities_authority_suspended',
                'sublease_agreement',
                'lease_renewal',
                'property_ownership_owner_are_suspended',
                'property_ownership_owner_are_deceased'
            ) NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasColumn('contracts', 'instrument_type')) {
            return;
        }

        DB::statement("
            ALTER TABLE `contracts`
            MODIFY `instrument_type` ENUM(
                'electronic',
                'electronic_tax_register',
                'property_ownership_owner_are_deceased_endowment',
                'sale_agreement',
                'electronic_deed_from_the_ministry_of_justice',
                'economic_cities_authority_suspended',
                'property_ownership_owner_are_deceased',
                'old_handwritten',
                'strong_argument'
            ) NULL
        ");
    }
};

