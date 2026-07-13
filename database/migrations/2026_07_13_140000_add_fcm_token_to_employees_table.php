<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || Schema::hasColumn('employees', 'fcm_token')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'fcm_token')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
