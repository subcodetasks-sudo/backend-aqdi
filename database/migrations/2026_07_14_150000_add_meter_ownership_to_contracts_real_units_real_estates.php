<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'electricity_meter_ownership')) {
                $table->enum('electricity_meter_ownership', ['owner', 'tenant'])->nullable();
            }
            if (! Schema::hasColumn('contracts', 'water_meter_ownership')) {
                $table->enum('water_meter_ownership', ['owner', 'tenant'])->nullable();
            }
        });

        Schema::table('real_units', function (Blueprint $table) {
            if (! Schema::hasColumn('real_units', 'electricity_meter_ownership')) {
                $table->enum('electricity_meter_ownership', ['owner', 'tenant'])->nullable();
            }
            if (! Schema::hasColumn('real_units', 'water_meter_ownership')) {
                $table->enum('water_meter_ownership', ['owner', 'tenant'])->nullable();
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'electricity_meter_ownership')) {
                $table->enum('electricity_meter_ownership', ['owner', 'tenant'])->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'water_meter_ownership')) {
                $table->enum('water_meter_ownership', ['owner', 'tenant'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'electricity_meter_ownership')) {
                $table->dropColumn('electricity_meter_ownership');
            }
            if (Schema::hasColumn('contracts', 'water_meter_ownership')) {
                $table->dropColumn('water_meter_ownership');
            }
        });

        Schema::table('real_units', function (Blueprint $table) {
            if (Schema::hasColumn('real_units', 'electricity_meter_ownership')) {
                $table->dropColumn('electricity_meter_ownership');
            }
            if (Schema::hasColumn('real_units', 'water_meter_ownership')) {
                $table->dropColumn('water_meter_ownership');
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (Schema::hasColumn('real_estates', 'electricity_meter_ownership')) {
                $table->dropColumn('electricity_meter_ownership');
            }
            if (Schema::hasColumn('real_estates', 'water_meter_ownership')) {
                $table->dropColumn('water_meter_ownership');
            }
        });
    }
};
