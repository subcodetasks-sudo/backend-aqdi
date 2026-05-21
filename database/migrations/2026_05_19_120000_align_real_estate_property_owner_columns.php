<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'property_owner_id_num')) {
                $table->string('property_owner_id_num')->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'property_owner_dob_hijri')) {
                $table->string('property_owner_dob_hijri')->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'property_owner_mobile')) {
                $table->string('property_owner_mobile')->nullable();
            }
            if (! Schema::hasColumn('real_estates', 'property_owner_iban')) {
                $table->string('property_owner_iban')->nullable();
            }
        });

        if (Schema::hasColumn('real_estates', 'national_num')) {
            DB::table('real_estates')
                ->whereNull('property_owner_id_num')
                ->whereNotNull('national_num')
                ->update(['property_owner_id_num' => DB::raw('national_num')]);
        }

        if (Schema::hasColumn('real_estates', 'dob_hijri')) {
            DB::table('real_estates')
                ->whereNull('property_owner_dob_hijri')
                ->whereNotNull('dob_hijri')
                ->update(['property_owner_dob_hijri' => DB::raw('dob_hijri')]);
        } elseif (Schema::hasColumn('real_estates', 'DOB')) {
            DB::table('real_estates')
                ->whereNull('property_owner_dob_hijri')
                ->whereNotNull('DOB')
                ->update(['property_owner_dob_hijri' => DB::raw('DOB')]);
        }

        if (Schema::hasColumn('real_estates', 'mobile')) {
            DB::table('real_estates')
                ->whereNull('property_owner_mobile')
                ->whereNotNull('mobile')
                ->update(['property_owner_mobile' => DB::raw('mobile')]);
        }

        if (Schema::hasColumn('real_estates', 'iban_bank')) {
            DB::table('real_estates')
                ->whereNull('property_owner_iban')
                ->whereNotNull('iban_bank')
                ->update(['property_owner_iban' => DB::raw('iban_bank')]);
        }
    }

    public function down(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            foreach ([
                'property_owner_id_num',
                'property_owner_dob_hijri',
                'property_owner_mobile',
                'property_owner_iban',
            ] as $column) {
                if (Schema::hasColumn('real_estates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
