<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\Setting;

/**
 * Resolve electricity/water meter fees for a contract when ownership is tenant.
 */
final class MeterFees
{
    /**
     * @return array{
     *     electricity_meter_fee: float,
     *     water_meter_fee: float,
     *     meter_fees_total: float
     * }
     */
    public static function forContract(Contract $contract, ?Setting $setting = null): array
    {
        $setting ??= Setting::query()->first();

        $electricity = 0.0;
        $water = 0.0;

        if ($setting) {
            $isHousing = $contract->contract_type === 'housing';

            if ($contract->electricity_meter_ownership === 'tenant') {
                $electricity = (float) ($isHousing
                    ? $setting->electricity_meter_fee_housing_tenant
                    : $setting->electricity_meter_fee_commercial_tenant);
            }

            if ($contract->water_meter_ownership === 'tenant') {
                $water = (float) ($isHousing
                    ? $setting->water_meter_fee_housing_tenant
                    : $setting->water_meter_fee_commercial_tenant);
            }
        }

        $electricity = max(0, $electricity);
        $water = max(0, $water);

        return [
            'electricity_meter_fee' => $electricity,
            'water_meter_fee' => $water,
            'meter_fees_total' => $electricity + $water,
        ];
    }

    public static function totalForContract(Contract $contract, ?Setting $setting = null): float
    {
        return self::forContract($contract, $setting)['meter_fees_total'];
    }
}
