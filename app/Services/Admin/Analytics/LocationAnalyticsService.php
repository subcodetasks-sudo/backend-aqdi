<?php

namespace App\Services\Admin\Analytics;

use App\Models\City;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LocationAnalyticsService
{
    use AnalyticsHelper;

    public function getLocationAnalytics(): array
    {
        $locations = [
            ['key' => 'riyadh', 'city_name' => 'الرياض'],
            ['key' => 'jeddah', 'city_name' => 'جدة'],
            ['key' => 'eastern', 'region_name' => 'الشرقية'],
            ['key' => 'dammam', 'city_name' => 'الدمام'],
        ];
        $result = [];
        foreach ($locations as $loc) {
            if (isset($loc['city_name'])) {
                $city = City::where('name_ar', $loc['city_name'])->first();
                $current = $city ? $this->getPaymentSumForCity($city->id) : 0;
                $previous = $city ? $this->getPaymentSumForCityPrevious($city->id) : 0;
            } else {
                $region = Region::where('name_ar', $loc['region_name'])->first();
                $current = $region ? $this->getPaymentSumForRegion($region->id) : 0;
                $previous = $region ? $this->getPaymentSumForRegionPrevious($region->id) : 0;
            }
            $result[] = [
                'label_ar' => $loc['city_name'] ?? $loc['region_name'],
                'value' => round($current, 2),
                'percentage_change' => $this->calculatePercentageChange($current, $previous),
                'type' => 'currency',
            ];
        }
        return $result;
    }

    protected function getPaymentSumForCity(int $cityId): float
    {
        return DB::table('payments')
            ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
            ->where('payments.status', 'success')
            ->where('contracts.property_city_id', $cityId)
            ->sum('payments.amount');
    }

    protected function getPaymentSumForCityPrevious(int $cityId): float
    {
        return DB::table('payments')
            ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
            ->where('payments.status', 'success')
            ->where('contracts.property_city_id', $cityId)
            ->where('payments.created_at', '<', Carbon::now()->startOfMonth())
            ->sum('payments.amount');
    }

    protected function getPaymentSumForRegion(int $regionId): float
    {
        return DB::table('payments')
            ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
            ->join('cities', 'contracts.property_city_id', '=', 'cities.id')
            ->where('payments.status', 'success')
            ->where('cities.region_id', $regionId)
            ->sum('payments.amount');
    }

    protected function getPaymentSumForRegionPrevious(int $regionId): float
    {
        return DB::table('payments')
            ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
            ->join('cities', 'contracts.property_city_id', '=', 'cities.id')
            ->where('payments.status', 'success')
            ->where('cities.region_id', $regionId)
            ->where('payments.created_at', '<', Carbon::now()->startOfMonth())
            ->sum('payments.amount');
    }
}
