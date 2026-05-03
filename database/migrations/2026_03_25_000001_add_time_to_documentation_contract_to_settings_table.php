<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Days added to contract created_at for documentation deadline (API field of same name).
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'time_to_documentation_contract')) {
                $table->unsignedInteger('time_to_documentation_contract')->nullable()->after('version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'time_to_documentation_contract')) {
                $table->dropColumn('time_to_documentation_contract');
            }
        });
    }
};
