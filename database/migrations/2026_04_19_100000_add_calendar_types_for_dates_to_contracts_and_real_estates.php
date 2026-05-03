<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'type_tenant_dob')) {
                $table->enum('type_tenant_dob', ['hijri', 'gregorian'])->default('hijri');
            }
            if (! Schema::hasColumn('contracts', 'type_dob_tenant_agent')) {
                $table->enum('type_dob_tenant_agent', ['hijri', 'gregorian'])->nullable();
            }
            if (! Schema::hasColumn('contracts', 'type_contract_starting_date')) {
                $table->enum('type_contract_starting_date', ['hijri', 'gregorian'])->default('hijri');
            }
            if (! Schema::hasColumn('contracts', 'type_instrument_history')) {
                $table->enum('type_instrument_history', ['hijri', 'gregorian'])->nullable();
            }
            if (! Schema::hasColumn('contracts', 'type_date_first_registration')) {
                $table->enum('type_date_first_registration', ['hijri', 'gregorian'])->nullable();
            }
            if (! Schema::hasColumn('contracts', 'type_agency_instrument_date_of_property_owner')) {
                $table->enum('type_agency_instrument_date_of_property_owner', ['hijri', 'gregorian'])->nullable();
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'type_instrument_history')) {
                $table->enum('type_instrument_history', ['hijri', 'gregorian'])->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'type_date_first_registration')) {
                $table->enum('type_date_first_registration', ['hijri', 'gregorian'])->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'type_agency_instrument_date_of_property_owner')) {
                $table->enum('type_agency_instrument_date_of_property_owner', ['hijri', 'gregorian'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            foreach ([
                'type_agency_instrument_date_of_property_owner',
                'type_date_first_registration',
                'type_instrument_history',
                'type_contract_starting_date',
                'type_dob_tenant_agent',
                'type_tenant_dob',
            ] as $col) {
                if (Schema::hasColumn('contracts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            foreach ([
                'type_agency_instrument_date_of_property_owner',
                'type_date_first_registration',
                'type_instrument_history',
            ] as $col) {
                if (Schema::hasColumn('real_estates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
