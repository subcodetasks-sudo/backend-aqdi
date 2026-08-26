<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeRefreshTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.url' => 'http://localhost',
            'admin_auth.access_token_ttl_seconds' => 900,
            'admin_auth.refresh_token_ttl_seconds' => 8 * 60 * 60,
            'admin_auth.remembered_refresh_token_ttl_seconds' => 30 * 24 * 60 * 60,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        URL::forceRootUrl('http://localhost');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach (['employee_refresh_tokens', 'personal_access_tokens', 'employees', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_login_issues_short_lived_access_and_remembered_refresh_tokens(): void
    {
        $this->createEmployee();

        $response = $this->postJson(route('employees.login', absolute: false), [
            'email' => 'employee@example.com',
            'password' => 'password',
            'remember_me' => true,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_expires_in', 900);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.refresh_token'));

        $accessToken = DB::table('personal_access_tokens')->first();
        $refreshToken = DB::table('employee_refresh_tokens')->first();

        $this->assertNotNull($accessToken->expires_at);
        $this->assertEqualsWithDelta(
            now()->addSeconds(900)->timestamp,
            \Carbon\Carbon::parse($accessToken->expires_at)->timestamp,
            2
        );
        $this->assertSame(1, (int) $refreshToken->remembered);
        $this->assertEqualsWithDelta(
            now()->addDays(30)->timestamp,
            \Carbon\Carbon::parse($refreshToken->expires_at)->timestamp,
            2
        );
    }

    public function test_refresh_rotates_token_and_rejects_reuse_of_the_old_token(): void
    {
        $this->createEmployee();
        $login = $this->postJson(route('employees.login', absolute: false), [
            'email' => 'employee@example.com',
            'password' => 'password',
        ])->assertOk();
        $oldRefreshToken = $login->json('data.refresh_token');

        $refreshed = $this->postJson(route('employees.refresh-token', absolute: false), [
            'refresh_token' => $oldRefreshToken,
        ])->assertOk()
            ->assertJsonPath('data.token_expires_in', 900);

        $this->assertNotSame($oldRefreshToken, $refreshed->json('data.refresh_token'));
        $this->assertNotEmpty($refreshed->json('data.token'));
        $this->assertDatabaseHas('employee_refresh_tokens', [
            'token_hash' => hash('sha256', $oldRefreshToken),
        ]);
        $this->assertNotNull(
            DB::table('employee_refresh_tokens')
                ->where('token_hash', hash('sha256', $oldRefreshToken))
                ->value('revoked_at')
        );

        $this->postJson(route('employees.refresh-token', absolute: false), [
            'refresh_token' => $oldRefreshToken,
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_the_submitted_refresh_token(): void
    {
        $this->createEmployee();
        $login = $this->postJson(route('employees.login', absolute: false), [
            'email' => 'employee@example.com',
            'password' => 'password',
        ])->assertOk();

        $this->withToken($login->json('data.token'))
            ->postJson(route('employees.logout', absolute: false), [
                'refresh_token' => $login->json('data.refresh_token'),
            ])
            ->assertOk();

        $this->postJson(route('employees.refresh-token', absolute: false), [
            'refresh_token' => $login->json('data.refresh_token'),
        ])->assertUnauthorized();
    }

    public function test_permission_denial_is_a_stable_403_while_missing_credentials_is_401(): void
    {
        $this->getJson(route('dashboard-analytics', absolute: false))->assertUnauthorized();

        $employee = $this->createEmployee();

        Sanctum::actingAs($employee);

        $this->getJson(route('reports.orders', absolute: false))
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => 'forbidden',
            ]);

        $this->getJson(route('dashboard-analytics', absolute: false))
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => 'forbidden',
            ]);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->string('name');
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->string('profile_image')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('snapchat')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('twitter')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_online')->default(false);
            $table->text('reason_of_block')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->string('token_hash', 64)->unique();
            $table->boolean('remembered')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }
}
