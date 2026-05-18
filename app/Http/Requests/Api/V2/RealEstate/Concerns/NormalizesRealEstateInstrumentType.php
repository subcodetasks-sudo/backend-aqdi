<?php

namespace App\Http\Requests\Api\V2\RealEstate\Concerns;

use App\Models\Contract;

trait NormalizesRealEstateInstrumentType
{
    /**
     * Map mobile aliases (e.g. electronic_deed_from_the_ministry_of_justice) to stored enum values.
     */
    protected function normalizeInstrumentTypeInput(): void
    {
        if (! $this->has('instrument_type')) {
            return;
        }

        $normalized = Contract::normalizeInstrumentType($this->input('instrument_type'));

        if ($normalized !== null) {
            $this->merge(['instrument_type' => $normalized]);
        }
    }
}
