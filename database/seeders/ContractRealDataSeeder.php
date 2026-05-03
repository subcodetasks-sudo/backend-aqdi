<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\ReaEstatType;
use App\Models\ReaEstatUsage;
use App\Models\TenantRole;
use App\Models\UnitType;
use App\Models\UsageUnit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds contracts with realistic Saudi-style data (owners, tenants, addresses, financials, unit details).
 * Requires: regions/cities, contract periods, payment types, property types/usages, unit types/usages, users.
 */
class ContractRealDataSeeder extends Seeder
{
    private const COUNT = 22;

    public function run(): void
    {
        if (User::query()->doesntExist()) {
            $this->command?->warn('ContractRealDataSeeder skipped: no users. Run UserSeeder first.');

            return;
        }

        if (City::query()->doesntExist()) {
            $this->command?->warn('ContractRealDataSeeder skipped: no cities. Run CitySeeder first.');

            return;
        }

        $cities = City::query()->get();
        $userIds = User::query()->pluck('id')->all();
        // Keep to values from create_contracts enum; expanded enum may not exist if column was created early.
        $instrumentTypes = ['electronic', 'old_handwritten', 'strong_argument'];
        $tenantRoleId = Schema::hasTable('tenant_roles') && TenantRole::query()->exists()
            ? TenantRole::query()->value('id')
            : null;

        $streetSets = [
            'housing' => [
                'طريق الملك فهد', 'شارع العليا', 'طريق الأمير محمد بن سلمان', 'شارع التحلية',
                'طريق الملك عبدالعزيز', 'شارع الأمير سلطان', 'حي الياسمين', 'حي النرجس',
            ],
            'commercial' => [
                'طريق الملك خالد', 'شارع التخصصي', 'طريق الأمير تركي', 'طريق الملك عبدالله',
                'شارع الأمير ماجد', 'طريق الدمام السريع', 'طريق الملك فهد التجاري',
            ],
        ];

        $neighborhoods = [
            'العليا', 'الملز', 'النسيم', 'الروضة', 'السليمانية', 'الورود', 'الرحمانية',
            'الشاطئ', 'الروابي', 'الفيصلية', 'الخبر الشمالية', 'الكورنيش', 'البلد', 'النسيم الغربي',
        ];

        $ownerNames = [
            ['فهد', 'القحطاني'], ['نورة', 'العنزي'], ['خالد', 'الرشيد'], ['لينا', 'العبيد'],
            ['عبدالله', 'المنصور'], ['هناء', 'البقمي'], ['فيصل', 'الدوسري'], ['ريم', 'الحارثي'],
        ];

        $tenantNames = [
            ['محمد', 'السبيعي'], ['أمل', 'الجهني'], ['طارق', 'القرني'], ['دانة', 'العسيري'],
            ['بندر', 'الخالدي'], ['شهد', 'اليامي'], ['نايف', 'الماجد'], ['جواهر', 'السبيعي'],
        ];

        for ($i = 0; $i < self::COUNT; $i++) {
            $city = $cities->random();
            $regionId = (int) $city->region_id;
            $contractType = ($i % 4 === 0) ? 'commercial' : 'housing';
            $completed = $i < 16;
            $step = $completed ? 7 : [3, 4, 5, 6][$i % 4];
            $ownership = ($i % 5 === 0) ? 'tenant' : 'owner';

            $period = ContractPeriod::query()
                ->where('contract_type', $contractType)
                ->inRandomOrder()
                ->first();
            $paymentType = PaymentType::query()
                ->where('contract_type', $contractType)
                ->inRandomOrder()
                ->first();
            $propertyType = ReaEstatType::query()
                ->where('contract_type', $contractType)
                ->inRandomOrder()
                ->first();
            $propertyUsage = ReaEstatUsage::query()
                ->where('contract_type', $contractType)
                ->inRandomOrder()
                ->first();
            $unitType = UnitType::query()
                ->where('contract_type', $contractType)
                ->inRandomOrder()
                ->first();
            $unitUsage = UsageUnit::query()
                ->where('contract_type', $contractType)
                ->inRandomOrder()
                ->first();

            if (! $period || ! $paymentType || ! $propertyType || ! $propertyUsage || ! $unitType || ! $unitUsage) {
                $this->command?->warn('ContractRealDataSeeder: missing reference rows for '.$contractType.'. Skipping one contract.');

                continue;
            }

            $owner = $ownerNames[$i % count($ownerNames)];
            $tenant = $tenantNames[$i % count($tenantNames)];
            $ownerFull = $owner[0].' '.$owner[1];
            $tenantFull = $tenant[0].' '.$tenant[1];
            $streets = $streetSets[$contractType];

            $ownerNationalId = '1'.str_pad((string) (100000000 + $i * 791), 9, '0', STR_PAD_LEFT);
            $tenantNationalId = '2'.str_pad((string) (100000000 + $i * 503), 9, '0', STR_PAD_LEFT);
            $instrumentType = $instrumentTypes[$i % count($instrumentTypes)];

            $annualRent = $contractType === 'commercial'
                ? (string) (80000 + $i * 4500 + random_int(0, 15000))
                : (string) (18000 + $i * 1200 + random_int(0, 8000));

            $latBase = match ((int) $city->region_id) {
                1 => [24.7136, 46.6753],
                2 => [21.4858, 39.1925],
                5 => [26.4207, 50.0888],
                6 => [18.2164, 42.5044],
                default => [24.0, 45.0],
            };

            $latitude = round($latBase[0] + (random_int(-800, 800) / 10000), 8);
            $longitude = round($latBase[1] + (random_int(-800, 800) / 10000), 8);

            $hijriStart = sprintf('%02d-%02d-%d', random_int(1, 28), random_int(1, 12), 1445 + ($i % 3));
            $ownerHijriDob = sprintf('%02d-%02d-%d', random_int(1, 28), random_int(1, 12), 1375 + ($i % 25));
            $tenantHijriDob = sprintf('%02d-%02d-%d', random_int(1, 28), random_int(1, 12), 1405 + ($i % 20));

            $createdAt = Carbon::now()->subDays(random_int(2, 120))->subHours(random_int(0, 20));

            $withAgent = $i % 7 === 0;
            $deceasedOwner = $i % 11 === 0;

            if ($deceasedOwner) {
                $instrumentType = 'strong_argument';
            }

            $payload = [
                'contract_type' => $contractType,
                'user_id' => $userIds[array_rand($userIds)],
                'app_or_web' => ($i % 2 === 0) ? 'web' : 'app',
                'contract_ownership' => $ownership,
                'instrument_type' => $instrumentType,
                'status' => null,
                'instrument_number' => (string) (4300000000 + $i * 137 + random_int(0, 99)),
                'instrument_history' => Carbon::create(2015 + ($i % 8), random_int(1, 12), random_int(1, 28))->toDateString(),
                'date_first_registration' => (string) (1420 + ($i % 6)),
                'real_estate_registry_number' => 'رقم صك '.(900000 + $i),
                'number_of_units_in_realestate' => (string) random_int(1, 24),
                'property_owner_is_deceased' => $deceasedOwner,
                'property_usages_id' => $propertyUsage->id,
                'property_city_id' => $city->id,
                'property_place_id' => $regionId,
                'property_type_id' => $propertyType->id,
                'neighborhood' => $neighborhoods[$i % count($neighborhoods)],
                'building_number' => (string) random_int(1, 9999),
                'postal_code' => str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT),
                'extra_figure' => '701'.$i,
                'number_of_floors' => (string) random_int(1, 12),
                'street' => $streets[$i % count($streets)],

                'property_owner_id_num' => $ownerNationalId,
                'property_owner_dob' => $ownerHijriDob,
                'property_owner_mobile' => '05'.str_pad((string) (10000000 + $i * 17), 8, '0', STR_PAD_LEFT),
                'property_owner_iban' => 'SA0380000000608010'.str_pad((string) (167500 + $i), 6, '0', STR_PAD_LEFT),

                'add_legal_agent_of_owner' => $withAgent,
                'id_num_of_property_owner_agent' => $withAgent ? '1'.str_pad((string) (200000000 + $i), 9, '0', STR_PAD_LEFT) : null,
                'dob_gregorian_of_property_owner_agent' => $withAgent ? Carbon::create(1980, 3, 15)->toDateString() : null,
                'dob_hijri_of_property_owner_agent' => $withAgent ? '10-06-1400' : null,
                'mobile_of_property_owner_agent' => $withAgent ? '05'.str_pad((string) (20000000 + $i), 8, '0', STR_PAD_LEFT) : null,
                'agency_number_in_instrument_of_property_owner' => $withAgent ? (string) (8800 + $i) : null,
                'agency_instrument_date_of_property_owner' => $withAgent ? Carbon::create(2022, 6, 1)->toDateString() : null,
                'agent_iban_of_property_owner' => $withAgent ? 'SA0380000000608020'.str_pad((string) (120000 + $i), 6, '0', STR_PAD_LEFT) : null,

                'tenant_id_num' => $tenantNationalId,
                'tenant_dob_gregorian' => Carbon::create(1988 + ($i % 25), random_int(1, 12), random_int(1, 28))->toDateString(),
                'tenant_dob_hijri' => $tenantHijriDob,
                'tenant_mobile' => '05'.str_pad((string) (30000000 + $i * 13), 8, '0', STR_PAD_LEFT),
                'name_owner' => $ownership === 'tenant' ? $ownerFull : $ownerFull,

                'add_legal_agent_of_tenant' => false,
                'tenant_entity' => 'person',
                'tenant_entity_unified_registry_number' => null,
                'tenant_entity_region_id' => null,
                'tenant_entity_city_id' => null,
                'authorization_type' => $i % 6 === 0 ? 'owner_and_representative_of_record' : null,

                'unit_number' => (string) ($i % 48 + 101),
                'unit_type_id' => $unitType->id,
                'tootal_rooms' => $contractType === 'housing' ? (string) random_int(1, 5) : '0',
                'floor_number' => (string) random_int(0, 8),
                'unit_area' => (string) (80 + $i * 3 + random_int(0, 40)),
                'electricity_meter_number' => 'EL-'.(100000 + $i),
                'water_meter_number' => 'WT-'.(200000 + $i),
                'number_of_unit_air_conditioners' => (string) random_int(1, 5),

                'contract_starting_date' => $hijriStart,
                'contract_term_in_years' => $period->id,
                'annual_rent_amount_for_the_unit' => $annualRent,
                'payment_type_id' => $paymentType->id,
                'daily_fine' => (string) random_int(50, 500),
                'sub_delay' => (bool) ($i % 4 === 0),
                'other_conditions' => 'يُمنع التأجير من الباطن دون موافقة خطية من المؤجر. الصيانة الدورية للمكيفات على المستأجر.',
                'premium_membership_for_free' => (bool) ($i % 5 === 0),
                'deposit' => (string) random_int(1000, 8000),
                'Guarantee_amount' => (string) random_int(500, 5000),
                'contract_period_id' => $period->id,
                'real_id' => null,
                'real_units_id' => null,
                'unit_usage_id' => $unitUsage->id,

                'client_account_holder_name' => $completed ? $tenantFull : null,
                'bank_account_number' => $completed ? str_pad((string) (3000000000000 + $i), 24, '0', STR_PAD_LEFT) : null,

                'The_number_of_the_toilet' => (string) random_int(1, 4),
                'The_number_of_halls' => (string) random_int(1, 3),
                'The_number_of_kitchens' => '1',
                'Gasmeter' => 'G-'.(5000 + $i),
                'Number_parking_spaces' => (string) random_int(0, 2),

                'rating' => $completed ? random_int(4, 5) : null,
                'rating_note' => $completed ? 'خدمة ممتازة وسرعة في إصدار العقد.' : null,
                'Services' => (bool) ($completed && $i % 3 === 0),
                'step' => $step,
                'is_completed' => $completed,
                'is_delete' => false,
                'is_real' => (bool) ($i % 9 === 0),
                'is_review' => (bool) ($completed && $i % 4 === 0),
                'notes' => 'بيانات تجريبية واقعية للاختبار — عقد إيجار '.$propertyType->name_ar.' في '.$city->name_ar.'.',
                'tenant_roles' => (bool) ($i % 8 === 0 && $tenantRoleId),
                'tenant_role_id' => ($i % 8 === 0 && $tenantRoleId) ? $tenantRoleId : null,
                'text_additional_terms' => $i % 5 === 0 ? 'يُسمح باصطحاب حيوان أليف صغير بشرط عدم الإزعاج.' : null,
                'additional_terms' => (bool) ($i % 5 === 0),
                'age_of_the_property' => random_int(2, 25),
                'number_of_units_per_floor' => (string) random_int(2, 8),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name_real_estate' => $contractType === 'commercial' ? 'مجمع الأعمال — مبنى '.($i + 1) : null,
            ];

            if (Schema::hasColumn('contracts', 'expiry_date')) {
                $payload['expiry_date'] = $completed
                    ? Carbon::now()->addYear()->addMonths(random_int(0, 6))->toDateString()
                    : null;
            }

            $contract = Contract::query()->create($payload);

            if ($completed) {
                Payment::query()->create([
                    'name' => 'عقد توثيق',
                    'amount' => random_int(150, 450),
                    'payment_date' => $createdAt->copy()->addDays(random_int(0, 3))->toDateString(),
                    'contract_uuid' => $contract->uuid,
                    'tran_currency' => 'SAR',
                    'payment_method' => ['mada', 'apple_pay', 'stc_pay'][$i % 3],
                    'status' => 'success',
                ]);
            }

            $contract->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        $this->command?->info('ContractRealDataSeeder: created '.self::COUNT.' contracts with realistic fields.');
    }
}
