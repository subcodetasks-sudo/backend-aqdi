<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        if (Schema::hasColumn('contracts', 'tenant_dob_hijri') && ! Schema::hasColumn('contracts', 'tenant_dob')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('tenant_dob_hijri', 'tenant_dob');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        if (Schema::hasColumn('contracts', 'tenant_dob') && ! Schema::hasColumn('contracts', 'tenant_dob_hijri')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('tenant_dob', 'tenant_dob_hijri');
            });
        }
    }
};
