<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'is_open')) {
                $table->boolean('is_open')->default(true)->after('open_payment');
            }
            if (! Schema::hasColumn('settings', 'working_hours')) {
                $table->string('working_hours')->nullable()->after('is_open');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'working_hours')) {
                $table->dropColumn('working_hours');
            }
            if (Schema::hasColumn('settings', 'is_open')) {
                $table->dropColumn('is_open');
            }
        });
    }
};
