<?php

use App\Support\Migrations\AltersInstrumentTypeEnumSafely;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use AltersInstrumentTypeEnumSafely;

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

        $this->modifyInstrumentTypeEnum('contracts', $enumSql);
        $this->modifyInstrumentTypeEnum('real_estates', $enumSql);
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

        $this->modifyInstrumentTypeEnum('contracts', <<<'SQL'
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
SQL);
    }
};
