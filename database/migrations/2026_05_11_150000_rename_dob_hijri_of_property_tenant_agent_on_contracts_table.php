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

        if (Schema::hasColumn('contracts', 'dob_hijri_of_property_tenant_agent') && ! Schema::hasColumn('contracts', 'dob_of_property_tenant_agent')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('dob_hijri_of_property_tenant_agent', 'dob_of_property_tenant_agent');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        if (Schema::hasColumn('contracts', 'dob_of_property_tenant_agent') && ! Schema::hasColumn('contracts', 'dob_hijri_of_property_tenant_agent')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('dob_of_property_tenant_agent', 'dob_hijri_of_property_tenant_agent');
            });
        }
    }
};
