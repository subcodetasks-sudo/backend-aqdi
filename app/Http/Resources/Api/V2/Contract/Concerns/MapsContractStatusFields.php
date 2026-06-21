<?php

namespace App\Http\Resources\Api\V2\Contract\Concerns;

trait MapsContractStatusFields
{
    /**
     * @return array{contract_status_id: int|null, contract_status_name: string|null}
     */
    protected function contractStatusFields(?string $defaultName = null): array
    {
        return self::contractStatusFieldsFor($this->resource, $defaultName);
    }

    /**
     * @return array{contract_status_id: int|null, contract_status_name: string|null}
     */
    protected static function contractStatusFieldsFor(?object $contract, ?string $defaultName = null): array
    {
        if ($contract === null) {
            return [
                'contract_status_id' => null,
                'contract_status_name' => $defaultName,
            ];
        }

        $name = optional($contract->contractStatus)->name;

        return [
            'contract_status_id' => $contract->contract_status_id,
            'contract_status_name' => $name ? trans($name) : $defaultName,
        ];
    }
}
