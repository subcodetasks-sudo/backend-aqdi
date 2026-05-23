<?php

use App\Support\Migrations\AltersInstrumentTypeEnumSafely;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use AltersInstrumentTypeEnumSafely;

    public function up(): void
    {
        $enum = <<<'SQL'
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
SQL;

        $this->modifyInstrumentTypeEnum('contracts', $enum);
        $this->modifyInstrumentTypeEnum('real_estates', $enum);
    }

    public function down(): void
    {
        $enum = <<<'SQL'
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
SQL;

        $this->modifyInstrumentTypeEnum('contracts', $enum);
        $this->modifyInstrumentTypeEnum('real_estates', $enum);
    }
};
