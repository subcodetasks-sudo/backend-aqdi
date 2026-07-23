<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_roles', 'service_definition')) {
                $table->text('service_definition')->nullable()->after('text_of_reason');
            }
            if (! Schema::hasColumn('tenant_roles', 'input_field_label')) {
                $table->string('input_field_label')->nullable()->after('service_definition');
            }
            if (! Schema::hasColumn('tenant_roles', 'input_field_type')) {
                $table->string('input_field_type', 20)->nullable()->after('input_field_label');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'tenant_role_values')) {
                $table->json('tenant_role_values')->nullable()->after('tenant_role_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_roles', function (Blueprint $table) {
            foreach (['service_definition', 'input_field_label', 'input_field_type'] as $column) {
                if (Schema::hasColumn('tenant_roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'tenant_role_values')) {
                $table->dropColumn('tenant_role_values');
            }
        });
    }
};
