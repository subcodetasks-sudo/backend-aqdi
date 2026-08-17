<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_status_histories')) {
            return;
        }

        if (! Schema::hasColumn('contract_status_histories', 'meta')) {
            Schema::table('contract_status_histories', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('source');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contract_status_histories') && Schema::hasColumn('contract_status_histories', 'meta')) {
            Schema::table('contract_status_histories', function (Blueprint $table) {
                $table->dropColumn('meta');
            });
        }
    }
};
