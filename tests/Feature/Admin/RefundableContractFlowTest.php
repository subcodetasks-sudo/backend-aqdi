<?php

namespace Tests\Feature\Admin;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Employee;
use App\Models\RefundableContract;
use App\Models\Role;
use App\Services\Admin\RefundableContractService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefundableContractFlowTest extends TestCase
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
        Schema::dropIfExists('refundable_contracts');
        Schema::dropIfExists('received_contracts');
        Schema::dropIfExists('contract_statuses');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_find_for_admin_accepts_refund_id_contract_uuid_and_contract_id(): void
    {
        [$employee, $contract, $refund] = $this->seedReturnOrder();

        $service = app(RefundableContractService::class);

        $this->assertSame($refund->id, $service->findForAdmin($refund->id)?->id);
        $this->assertSame($refund->id, $service->findForAdmin($contract->uuid)?->id);
        $this->assertSame($refund->id, $service->findForAdmin($contract->id)?->id);
    }

    public function test_approve_sets_admin_confirmed_and_accept_retrun_contract_atomically(): void
    {
        [$employee, $contract, $refund] = $this->seedReturnOrder();
        $service = app(RefundableContractService::class);

        $updated = $service->applyAdminUpdate($refund, [
            'admin_confirmed' => true,
            'refund_amount' => 150,
            'notes' => 'تم التحويل',
        ], $employee);

        $this->assertTrue((bool) $updated->admin_confirmed);
        $this->assertFalse((bool) $updated->is_refunded);

        $contract->refresh();
        $this->assertTrue((bool) $contract->accept_retrun_contract);
        $this->assertSame($employee->id, $contract->accept_retrun_contract_employee_id);
    }

    public function test_reject_and_retract_use_distinct_outcomes(): void
    {
        [$employee, $contract, $refund] = $this->seedReturnOrder();
        $service = app(RefundableContractService::class);

        $rejected = $service->applyAdminUpdate($refund, [
            'admin_confirmed' => false,
            'refund_amount' => 0,
            'notes' => 'لم تتم الموافقة من الإدارة',
        ], $employee);

        $this->assertFalse((bool) $rejected->admin_confirmed);
        $contract->refresh();
        $this->assertSame(ContractStatus::RETURN_ID, (int) $contract->contract_status_id);

        [, $contract2, $refund2] = $this->seedReturnOrder();
        $service->applyAdminUpdate($refund2, [
            'action' => 'retract',
            'notes' => 'التراجع عن الاسترجاع وتصنيف الطلب كطلب مكتمل',
        ], $employee);

        $contract2->refresh();
        $this->assertSame(ContractStatus::RECEIVED_ID, (int) $contract2->contract_status_id);
    }

    public function test_management_approval_summary_returns_global_kpi_counts(): void
    {
        $this->seedReturnOrder();
        $service = app(RefundableContractService::class);
        $summary = $service->buildIndexSummary('total');

        $this->assertArrayHasKey('pending', $summary['management_approval']);
        $this->assertArrayHasKey('processing', $summary['management_approval']);
        $this->assertArrayHasKey('completed', $summary['management_approval']);
        $this->assertArrayHasKey('rejected', $summary['management_approval']);
        $this->assertSame(1, $summary['management_approval']['pending']);
    }

    public function test_resolve_period_accepts_created_at_all_alias(): void
    {
        $service = app(RefundableContractService::class);

        $this->assertSame('total', $service->resolvePeriod(null, 'all'));
    }

    /**
     * @return array{0: Employee, 1: Contract, 2: RefundableContract}
     */
    private function seedReturnOrder(): array
    {
        static $counter = 0;
        $counter++;

        $role = Role::query()->create([
            'name' => 'admin',
            'title_ar' => 'Admin',
            'title_en' => 'Admin',
            'is_active' => true,
        ]);

        $employee = Employee::query()->create([
            'role_id' => $role->id,
            'name' => 'Tester '.$counter,
            'email' => "tester{$counter}@example.com",
            'password' => Hash::make('secret'),
            'is_active' => true,
        ]);

        $userId = DB::table('users')->insertGetId([
            'fname' => 'Client',
            'lname' => 'One',
            'mobile' => '0500000000',
            'email' => "client{$counter}@example.com",
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contract = Contract::query()->create([
            'user_id' => $userId,
            'contract_status_id' => ContractStatus::RETURN_ID,
            'contract_type' => 'housing',
            'instrument_type' => 'electronic',
            'step' => 3,
            'is_completed' => true,
            'is_delete' => 0,
            'is_draft' => 0,
            'app_or_web' => 'web',
        ]);

        DB::table('received_contracts')->insert([
            'contract_id' => $contract->id,
            'employee_id' => $employee->id,
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $refund = RefundableContract::query()->create([
            'contract_id' => $contract->id,
            'user_id' => $userId,
            'employee_id' => $employee->id,
            'refund_amount' => 150,
            'admin_confirmed' => null,
            'is_refunded' => false,
        ]);

        return [$employee, $contract, $refund];
    }

    private function createMinimalSchema(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('contract_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('contract_statuses')->insert([
            ['id' => ContractStatus::RETURN_ID, 'name' => 'استرجاع', 'color' => '#f00', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => ContractStatus::RECEIVED_ID, 'name' => 'مستلم', 'color' => '#0f0', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('contract_status_id')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('instrument_type')->nullable();
            $table->unsignedTinyInteger('step')->default(3);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->string('app_or_web')->nullable();
            $table->boolean('accept_retrun_contract')->default(false);
            $table->unsignedBigInteger('accept_retrun_contract_employee_id')->nullable();
            $table->timestamps();
        });

        Schema::create('received_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('refundable_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('admin_confirmed')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->timestamps();
        });
    }
}
