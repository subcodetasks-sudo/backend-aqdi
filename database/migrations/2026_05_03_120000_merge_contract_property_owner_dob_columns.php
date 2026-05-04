<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contracts', 'property_owner_dob')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->text('property_owner_dob')->nullable();
            });
        }

        $rows = DB::table('contracts')->select('*')->get();

        foreach ($rows as $row) {
            $type = strtolower(trim((string) (
                $row->type_dob_property_owner
                    ?? $row->type_dob
                    ?? 'hijri'
            )));

            $value = $type === 'gregorian'
                ? ($row->property_owner_dob_gregorian ?? null)
                : ($row->property_owner_dob_hijri ?? null);

            if (($value === null || $value === '') && ! empty($row->property_owner_dob_hijri)) {
                $value = $row->property_owner_dob_hijri;
            }
            if (($value === null || $value === '') && ! empty($row->property_owner_dob_gregorian)) {
                $value = $row->property_owner_dob_gregorian;
            }

            DB::table('contracts')->where('id', $row->id)->update([
                'property_owner_dob' => $value,
            ]);
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'property_owner_dob_hijri')) {
                $table->dropColumn('property_owner_dob_hijri');
            }
            if (Schema::hasColumn('contracts', 'property_owner_dob_gregorian')) {
                $table->dropColumn('property_owner_dob_gregorian');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'property_owner_dob_hijri')) {
                $table->string('property_owner_dob_hijri')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'property_owner_dob_gregorian')) {
                $table->string('property_owner_dob_gregorian')->nullable();
            }
        });

        $rows = DB::table('contracts')->select('*')->get();

        foreach ($rows as $row) {
            $type = strtolower(trim((string) (
                $row->type_dob_property_owner
                    ?? $row->type_dob
                    ?? 'hijri'
            )));

            $dob = $row->property_owner_dob ?? null;

            DB::table('contracts')->where('id', $row->id)->update([
                'property_owner_dob_hijri' => $type !== 'gregorian' ? $dob : null,
                'property_owner_dob_gregorian' => $type === 'gregorian' ? $dob : null,
            ]);
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'property_owner_dob')) {
                $table->dropColumn('property_owner_dob');
            }
        });
    }
};
