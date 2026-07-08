<?php

namespace Tests\Unit;

use App\Models\ContractPaidByEmployee;
use App\Models\Employee;
use App\Models\Payment;
use App\Services\MoyasarPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoyasarEmployeePaidContractIpnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('contract_paid_by_employees');
        Schema::dropIfExists('employees');

        parent::tearDown();
    }

    private function createMinimalSchema(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('contract_paid_by_employees');
        Schema::dropIfExists('employees');

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('contract_paid_by_employees', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_uuid')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('customer_mobile', 32);
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('contract_uuid')->nullable();
            $table->string('tran_currency')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    public function test_ipn_marks_employee_paid_contract_as_paid_without_contract_row(): void
    {
        config([
            'services.moyasar.secret_key' => 'test_secret',
            'services.moyasar.base_url' => 'https://api.moyasar.com',
        ]);

        Http::fake([
            'https://api.moyasar.com/v1/payments/pay_test' => Http::response([
                'id' => 'pay_test',
                'status' => 'paid',
                'amount' => 50000,
                'currency' => 'SAR',
                'source' => ['type' => 'creditcard'],
                'metadata' => ['contract_uuid' => '123456'],
            ], 200),
        ]);

        $employee = Employee::query()->create([
            'name' => 'IPN Employee',
            'email' => 'ipn-employee@test.local',
            'password' => Hash::make('secret'),
            'is_active' => true,
        ]);

        ContractPaidByEmployee::query()->create([
            'contract_uuid' => '123456',
            'employee_id' => $employee->id,
            'customer_mobile' => '0512345678',
            'amount' => 500,
            'is_paid' => false,
        ]);

        $service = app(MoyasarPaymentService::class);
        $service->processIpn(new Request([
            'id' => 'pay_test',
            'status' => 'paid',
        ]), '123456');

        $this->assertTrue(
            (bool) ContractPaidByEmployee::query()->where('contract_uuid', '123456')->value('is_paid')
        );

        $this->assertDatabaseHas('payments', [
            'contract_uuid' => '123456',
            'status' => 'success',
        ]);
    }

    public function test_payment_status_payload_works_for_employee_paid_contract_without_contract_row(): void
    {
        config([
            'services.moyasar.secret_key' => 'test_secret',
            'services.moyasar.base_url' => 'https://api.moyasar.com',
        ]);

        $employee = Employee::query()->create([
            'name' => 'Status Employee',
            'email' => 'status-employee@test.local',
            'password' => Hash::make('secret'),
            'is_active' => true,
        ]);

        ContractPaidByEmployee::query()->create([
            'contract_uuid' => '654321',
            'employee_id' => $employee->id,
            'customer_mobile' => '0511111111',
            'amount' => 300,
            'is_paid' => true,
            'notes' => 'paid in branch',
        ]);

        Payment::query()->create([
            'amount' => 300,
            'contract_uuid' => '654321',
            'tran_currency' => 'SAR',
            'status' => 'success',
            'payment_date' => now(),
        ]);

        $payload = app(MoyasarPaymentService::class)->paymentStatusPayload('654321', 'success');

        $this->assertSame('success', $payload['result']);
        $this->assertSame('654321', $payload['contract_uuid']);
        $this->assertNull($payload['contract_id']);
        $this->assertFalse($payload['is_completed']);
        $this->assertTrue($payload['employee_paid_record']['is_paid']);
        $this->assertSame('paid in branch', $payload['employee_paid_record']['notes']);
    }
}
