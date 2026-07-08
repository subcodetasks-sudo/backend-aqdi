<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_paid_by_employees')) {
            return;
        }

        if (Schema::hasColumn('contract_paid_by_employees', 'notes')) {
            return;
        }

        Schema::table('contract_paid_by_employees', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('is_paid');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contract_paid_by_employees') || ! Schema::hasColumn('contract_paid_by_employees', 'notes')) {
            return;
        }

        Schema::table('contract_paid_by_employees', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
