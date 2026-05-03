<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'type_dob_property_owner')) {
                $table->enum('type_dob_property_owner', ['hijri', 'gregorian'])
                    ->default('hijri')
                    ->after('type_dob');
            }

            if (! Schema::hasColumn('contracts', 'type_dob_property_owner_agent')) {
                $table->enum('type_dob_property_owner_agent', ['hijri', 'gregorian'])
                    ->nullable()
                    ->after('type_dob_property_owner');
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'type_dob_property_owner')) {
                $table->enum('type_dob_property_owner', ['hijri', 'gregorian'])
                    ->default('hijri')
                    ->after('property_owner_id_num');
            }

            if (! Schema::hasColumn('real_estates', 'type_dob_property_owner_agent')) {
                $table->enum('type_dob_property_owner_agent', ['hijri', 'gregorian'])
                    ->nullable()
                    ->after('type_dob_property_owner');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'type_dob_property_owner_agent')) {
                $table->dropColumn('type_dob_property_owner_agent');
            }
            if (Schema::hasColumn('contracts', 'type_dob_property_owner')) {
                $table->dropColumn('type_dob_property_owner');
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (Schema::hasColumn('real_estates', 'type_dob_property_owner_agent')) {
                $table->dropColumn('type_dob_property_owner_agent');
            }
            if (Schema::hasColumn('real_estates', 'type_dob_property_owner')) {
                $table->dropColumn('type_dob_property_owner');
            }
        });
    }
};
