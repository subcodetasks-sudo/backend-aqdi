<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_statuses') && ! Schema::hasColumn('contract_statuses', 'client_explanation')) {
            Schema::table('contract_statuses', function (Blueprint $table) {
                $table->text('client_explanation')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('draft_contract_statuses') && ! Schema::hasColumn('draft_contract_statuses', 'client_explanation')) {
            Schema::table('draft_contract_statuses', function (Blueprint $table) {
                $table->text('client_explanation')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('contract_status_histories') && ! Schema::hasColumn('contract_status_histories', 'client_explanation')) {
            Schema::table('contract_status_histories', function (Blueprint $table) {
                $table->text('client_explanation')->nullable()->after('status_description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contract_statuses') && Schema::hasColumn('contract_statuses', 'client_explanation')) {
            Schema::table('contract_statuses', function (Blueprint $table) {
                $table->dropColumn('client_explanation');
            });
        }

        if (Schema::hasTable('draft_contract_statuses') && Schema::hasColumn('draft_contract_statuses', 'client_explanation')) {
            Schema::table('draft_contract_statuses', function (Blueprint $table) {
                $table->dropColumn('client_explanation');
            });
        }

        if (Schema::hasTable('contract_status_histories') && Schema::hasColumn('contract_status_histories', 'client_explanation')) {
            Schema::table('contract_status_histories', function (Blueprint $table) {
                $table->dropColumn('client_explanation');
            });
        }
    }
};
