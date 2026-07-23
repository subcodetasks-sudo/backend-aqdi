<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_roles', 'icon')) {
                $table->string('icon')->nullable()->after('input_field_type');
            }
            if (! Schema::hasColumn('tenant_roles', 'input_icon')) {
                $table->string('input_icon')->nullable()->after('icon');
            }
            if (! Schema::hasColumn('tenant_roles', 'pop')) {
                $table->boolean('pop')->default(false)->after('input_icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_roles', function (Blueprint $table) {
            foreach (['icon', 'input_icon', 'pop'] as $column) {
                if (Schema::hasColumn('tenant_roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
