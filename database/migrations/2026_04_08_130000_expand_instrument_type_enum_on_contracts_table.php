<?php

use App\Support\Migrations\AltersInstrumentTypeEnumSafely;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use AltersInstrumentTypeEnumSafely;

    public function up(): void
    {
        $this->modifyInstrumentTypeEnum('contracts', <<<'SQL'
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
SQL);
    }

    public function down(): void
    {
        $this->modifyInstrumentTypeEnum('contracts', <<<'SQL'
MODIFY `instrument_type` ENUM(
    'electronic',
    'old_handwritten',
    'strong_argument'
) NULL
SQL);
    }
};
