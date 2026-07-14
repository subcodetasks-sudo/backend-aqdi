<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'electricity_meter_fee_commercial_tenant')) {
                $table->decimal('electricity_meter_fee_commercial_tenant', 12, 2)->nullable()->after('sms_employee');
            }
            if (! Schema::hasColumn('settings', 'electricity_meter_fee_housing_tenant')) {
                $table->decimal('electricity_meter_fee_housing_tenant', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'water_meter_fee_commercial_tenant')) {
                $table->decimal('water_meter_fee_commercial_tenant', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'water_meter_fee_housing_tenant')) {
                $table->decimal('water_meter_fee_housing_tenant', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'electricity_meter_fee_commercial_tenant',
                'electricity_meter_fee_housing_tenant',
                'water_meter_fee_commercial_tenant',
                'water_meter_fee_housing_tenant',
            ]);
        });
    }
};
