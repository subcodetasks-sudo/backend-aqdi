<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractWhatsApp;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\RealEstate;
use App\Models\ReceivedContract;
use App\Models\RefundableContract;
use App\Models\Region;
use App\Models\UnitsReal;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnalysisSeeder extends Seeder
{
    /**
     * Seed data for all dashboard analytics sections.
     * Run after: RegionSeeder, CitySeeder, UserSeeder, EmployeeSeeder, BlogSeeder.
     */
    public function run(): void
    {
        $users = User::all();
        $employees = Employee::all();
        $cities = City::all()->keyBy('name_ar');
        $riyadhId = $cities->get('الرياض')?->id ?? 1;
        $jeddahId = $cities->get('جدة')?->id ?? 5;
        $dammamId = $cities->get('الدمام')?->id ?? 12;
        $easternRegionId = Region::where('name_ar', 'الشرقية')->value('id') ?? 5;

        if ($users->isEmpty() || $employees->isEmpty()) {
            $this->command->warn('AnalysisSeeder requires UserSeeder and EmployeeSeeder to run first.');
            return;
        }

        $userIds = $users->pluck('id')->all();
        $employeeIds = $employees->pluck('id')->all();

        // 1. Visitors
        $this->seedVisitors();

        // 2. Contracts with various steps, cities, completion status, spread over time
        $contracts = $this->seedContracts($userIds, $riyadhId, $jeddahId, $dammamId, $easternRegionId);

        // 3. Payments (success/failed) linked to contracts
        $this->seedPayments($contracts);

        // 4. ContractWhatsapp (completed/incomplete)
        $this->seedContractWhatsapp();

        // 5. ReceivedContract (employee received contracts)
        $this->seedReceivedContracts($contracts, $employeeIds);

        // 6. RefundableContract
        $this->seedRefundableContracts($contracts, $employeeIds);

        // 7. Expenses (spread over periods)
        $this->seedExpenses($employeeIds);

        // 8. RealEstate & UnitsReal
        $this->seedRealEstatesAndUnits($userIds, $riyadhId, $jeddahId, $dammamId);
    }

    private function seedVisitors(): void
    {
        foreach (range(1, 50) as $i) {
            Visitor::create([
                'ip_address' => '192.168.1.' . $i,
                'time_visit' => rand(60, 3600),
                'created_at' => Carbon::now()->subDays(rand(0, 90)),
            ]);
        }
    }

    private function seedContracts(array $userIds, int $riyadhId, int $jeddahId, int $dammamId, int $easternRegionId): array
    {
        $cityIds = [$riyadhId, $jeddahId, $dammamId];
        $contracts = [];

        // Step distribution for order_transfer_analytics
        $stepConfigs = [
            ['step' => 0, 'is_completed' => 0],
            ['step' => 1, 'is_completed' => 0],
            ['step' => 2, 'is_completed' => 0],
            ['step' => 3, 'is_completed' => 0],
            ['step' => 4, 'is_completed' => 0],
            ['step' => 5, 'is_completed' => 0],
            ['step' => 6, 'is_completed' => 1],
        ];

        $periods = [
            'today' => Carbon::today(),
            'week' => Carbon::now()->subDays(rand(1, 6)),
            'month' => Carbon::now()->subDays(rand(7, 30)),
            'year' => Carbon::now()->subDays(rand(31, 365)),
        ];

        foreach ($stepConfigs as $config) {
            foreach ($periods as $period => $baseDate) {
                $count = $period === 'today' ? rand(2, 5) : rand(1, 4);
                for ($i = 0; $i < $count; $i++) {
                    $createdAt = $period === 'today' ? Carbon::today()->subHours(rand(0, 23)) : $baseDate->copy()->subDays(rand(0, 2));
                    $cityId = $cityIds[array_rand($cityIds)];
                    $regionId = $cityId === $dammamId ? $easternRegionId : ($cityId === $riyadhId ? 1 : 2);

                    $contract = Contract::create([
                        'contract_type' => ['housing', 'commercial'][rand(0, 1)],
                        'user_id' => $userIds[array_rand($userIds)],
                        'app_or_web' => ['app', 'web'][rand(0, 1)],
                        'step' => $config['step'],
                        'is_completed' => $config['is_completed'],
                        'is_delete' => 0,
                        'property_city_id' => $cityId,
                        'property_place_id' => $regionId,
                        'created_at' => $createdAt,
                    ]);
                    $contracts[] = $contract;
                }
            }
        }

        return $contracts;
    }

    private function seedPayments(array $contracts): void
    {
        $periods = [
            Carbon::today(),
            Carbon::now()->subDays(rand(1, 6)),
            Carbon::now()->subDays(rand(7, 30)),
            Carbon::now()->subDays(rand(31, 365)),
        ];

        foreach ($contracts as $contract) {
            $status = $contract->is_completed ? (rand(1, 10) > 2 ? 'success' : 'failed') : (rand(1, 10) > 7 ? 'success' : 'pending');
            $createdAt = $periods[array_rand($periods)]->copy()->subHours(rand(0, 12));

            Payment::create([
                'name' => 'عقد توثيق',
                'amount' => rand(100, 5000),
                'payment_date' => $createdAt->toDateString(),
                'contract_uuid' => $contract->uuid,
                'tran_currency' => 'SAR',
                'payment_method' => 'mada',
                'status' => $status,
                'created_at' => $createdAt,
            ]);
        }
    }

    private function seedContractWhatsapp(): void
    {
        $periods = [
            Carbon::today(),
            Carbon::now()->subDays(rand(1, 30)),
        ];

        for ($i = 0; $i < 15; $i++) {
            ContractWhatsApp::create([
                'mobile_number' => '9665' . rand(10000000, 59999999),
                'addition_date' => $periods[array_rand($periods)],
                'contract_type' => ['commercial', 'residential'][rand(0, 1)],
                'is_complete' => (bool) rand(0, 1),
                'amount_paid_by_client' => rand(100, 2000),
                'rental_fees' => rand(500, 5000),
            ]);
        }
    }

    private function seedReceivedContracts(array $contracts, array $employeeIds): void
    {
        $sample = array_slice($contracts, 0, min(20, count($contracts)));
        foreach ($sample as $contract) {
            ReceivedContract::create([
                'contract_id' => $contract->id,
                'employee_id' => $employeeIds[array_rand($employeeIds)],
                'date_of_received' => $contract->created_at?->toDateString() ?? Carbon::today(),
            ]);
        }
    }

    private function seedRefundableContracts(array $contracts, array $employeeIds): void
    {
        $completed = array_filter($contracts, fn($c) => $c->is_completed);
        $sample = array_slice($completed, 0, min(8, count($completed)));
        foreach ($sample as $contract) {
            RefundableContract::create([
                'contract_id' => $contract->id,
                'employee_id' => $employeeIds[array_rand($employeeIds)],
                'refund_amount' => rand(100, 1500),
                'notes' => 'استرجاع طلب',
            ]);
        }
    }

    private function seedExpenses(array $employeeIds): void
    {
        $rows = [
            ['notes' => 'مصاريف يومية', 'amount' => 500],
            ['notes' => 'مصاريف أسبوعية', 'amount' => 1200],
            ['notes' => 'مصاريف شهرية', 'amount' => 3500],
            ['notes' => 'مصاريف سنوية', 'amount' => 8500],
        ];
        $periods = [
            Carbon::today(),
            Carbon::now()->subDays(rand(1, 6)),
            Carbon::now()->subDays(rand(7, 30)),
            Carbon::now()->subDays(rand(31, 365)),
        ];
        foreach ($rows as $row) {
            Expense::create([
                'employee_id' => $employeeIds[array_rand($employeeIds)],
                'amount' => $row['amount'],
                'notes' => $row['notes'],
                'created_at' => $periods[array_rand($periods)],
            ]);
        }
    }

    private function seedRealEstatesAndUnits(array $userIds, int $riyadhId, int $jeddahId, int $dammamId): void
    {
        $cityIds = [$riyadhId, $jeddahId, $dammamId];
        $periods = [
            Carbon::today(),
            Carbon::now()->subDays(rand(1, 6)),
            Carbon::now()->subDays(rand(7, 30)),
            Carbon::now()->subDays(rand(31, 365)),
        ];

        foreach (array_slice($userIds, 0, 6) as $userId) {
            $createdAt = $periods[array_rand($periods)];
            $estate = RealEstate::create([
                'user_id' => $userId,
                'property_city_id' => $cityIds[array_rand($cityIds)],
                'contract_type' => 'housing',
                'created_at' => $createdAt,
            ]);

            if (rand(0, 1)) {
                UnitsReal::create([
                    'real_estates_units_id' => $estate->id,
                    'user_id' => $userId,
                    'unit_area' => (string) rand(80, 300),
                    'Services' => 0,
                    'created_at' => $createdAt->copy()->addHours(1),
                ]);
            }
        }
    }
}
