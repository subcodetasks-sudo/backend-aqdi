<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy: fees first lived on setting_contracts then moved to settings.
 * Keep this migration safe if columns were never added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_contracts')) {
            return;
        }

        Schema::table('setting_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('setting_contracts', 'electricity_meter_fee_commercial_tenant')) {
                $table->decimal('electricity_meter_fee_commercial_tenant', 12, 2)->nullable()->after('label');
            }
            if (! Schema::hasColumn('setting_contracts', 'electricity_meter_fee_housing_tenant')) {
                $table->decimal('electricity_meter_fee_housing_tenant', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('setting_contracts', 'water_meter_fee_commercial_tenant')) {
                $table->decimal('water_meter_fee_commercial_tenant', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('setting_contracts', 'water_meter_fee_housing_tenant')) {
                $table->decimal('water_meter_fee_housing_tenant', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_contracts')) {
            return;
        }

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
};
