<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep contracts and real_estates instrument_type ENUMs aligned (single source of truth).
     */
    private function fullInstrumentTypeEnumModifySql(): string
    {
        return trim(<<<'SQL'
MODIFY `instrument_type` ENUM(
    'electronic',
    'old_handwritten',
    'strong_argument',
    'electronic_tax_register',
    'property_ownership_owner_are_deceased_endowment',
    'property_ownership_owner_is_endowment',
    'sale_agreement',
    'electronic_deed_from_the_ministry_of_justice',
    'economic_cities_authority_suspended',
    'sublease_agreement',
    'lease_renewal',
    'property_ownership_owner_are_suspended',
    'property_ownership_owner_are_deceased'
) NULL
SQL);
    }

    /**
     * real_estates once used typo'd enum members (2026_03_11_040254). MySQL MODIFY ENUM
     * fails if an existing value is not listed — normalize before ALTER.
     */
    private function normalizeLegacyRealEstateInstrumentTypes(): void
    {
        if (! Schema::hasTable('real_estates') || ! Schema::hasColumn('real_estates', 'instrument_type')) {
            return;
        }

        $map = [
            'property_ownership__owner_are_deceased_endowment' => 'property_ownership_owner_are_deceased_endowment',
            'property_ownership__owner_are_deceased' => 'property_ownership_owner_are_deceased',
        ];

        foreach ($map as $from => $to) {
            DB::table('real_estates')->where('instrument_type', $from)->update(['instrument_type' => $to]);
        }
    }

    public function up(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'copy_of_the_endowment_registration_certificate')) {
                $table->string('copy_of_the_endowment_registration_certificate')->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'copy_of_the_trusteeship_deed')) {
                $table->string('copy_of_the_trusteeship_deed')->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'is_multiple_trusteeship_deed_copy')) {
                $table->boolean('is_multiple_trusteeship_deed_copy')->default(false);
            }
            if (! Schema::hasColumn('real_estates', 'copy_of_guardians_power_of_attorney_for_agent')) {
                $table->string('copy_of_guardians_power_of_attorney_for_agent')->nullable();
            }
        });

        $enumSql = $this->fullInstrumentTypeEnumModifySql();

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'instrument_type')) {
            DB::statement('ALTER TABLE `contracts` '.$enumSql);
        }

        if (Schema::hasTable('real_estates') && Schema::hasColumn('real_estates', 'instrument_type')) {
            $this->normalizeLegacyRealEstateInstrumentTypes();
            DB::statement('ALTER TABLE `real_estates` '.$enumSql);
        }
    }

    public function down(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            if (Schema::hasColumn('real_estates', 'copy_of_guardians_power_of_attorney_for_agent')) {
                $table->dropColumn('copy_of_guardians_power_of_attorney_for_agent');
            }
            if (Schema::hasColumn('real_estates', 'is_multiple_trusteeship_deed_copy')) {
                $table->dropColumn('is_multiple_trusteeship_deed_copy');
            }
            if (Schema::hasColumn('real_estates', 'copy_of_the_trusteeship_deed')) {
                $table->dropColumn('copy_of_the_trusteeship_deed');
            }
            if (Schema::hasColumn('real_estates', 'copy_of_the_endowment_registration_certificate')) {
                $table->dropColumn('copy_of_the_endowment_registration_certificate');
            }
        });

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'instrument_type')) {
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
    }
};
