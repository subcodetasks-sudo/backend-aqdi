<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_contracts', function (Blueprint $table) {
            $columns = [
                'electricity_meter_fee_commercial_tenant',
                'electricity_meter_fee_housing_tenant',
                'water_meter_fee_commercial_tenant',
                'water_meter_fee_housing_tenant',
            ];

            $drop = array_values(array_filter(
                $columns,
                static fn (string $column): bool => Schema::hasColumn('setting_contracts', $column)
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('setting_contracts', function (Blueprint $table) {
            $table->decimal('electricity_meter_fee_commercial_tenant', 12, 2)->nullable()->after('label');
            $table->decimal('electricity_meter_fee_housing_tenant', 12, 2)->nullable()->after('electricity_meter_fee_commercial_tenant');
            $table->decimal('water_meter_fee_commercial_tenant', 12, 2)->nullable()->after('electricity_meter_fee_housing_tenant');
            $table->decimal('water_meter_fee_housing_tenant', 12, 2)->nullable()->after('water_meter_fee_commercial_tenant');
        });
    }
};
