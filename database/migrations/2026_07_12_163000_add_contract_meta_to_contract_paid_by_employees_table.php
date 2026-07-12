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

        Schema::table('contract_paid_by_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_paid_by_employees', 'contract_type')) {
                $table->enum('contract_type', ['housing', 'commercial'])->nullable()->after('customer_mobile');
            }

            if (! Schema::hasColumn('contract_paid_by_employees', 'contract_period_id')) {
                $table->foreignId('contract_period_id')
                    ->nullable()
                    ->after('contract_type')
                    ->constrained('contract_periods')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contract_paid_by_employees', 'draft_contract_number')) {
                $table->string('draft_contract_number', 32)->nullable()->after('contract_period_id');
            }

            if (! Schema::hasColumn('contract_paid_by_employees', 'draft_contract_id')) {
                $table->foreignId('draft_contract_id')
                    ->nullable()
                    ->after('draft_contract_number')
                    ->constrained('contracts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contract_paid_by_employees')) {
            return;
        }

        Schema::table('contract_paid_by_employees', function (Blueprint $table) {
            if (Schema::hasColumn('contract_paid_by_employees', 'draft_contract_id')) {
                $table->dropConstrainedForeignId('draft_contract_id');
            }

            if (Schema::hasColumn('contract_paid_by_employees', 'draft_contract_number')) {
                $table->dropColumn('draft_contract_number');
            }

            if (Schema::hasColumn('contract_paid_by_employees', 'contract_period_id')) {
                $table->dropConstrainedForeignId('contract_period_id');
            }

            if (Schema::hasColumn('contract_paid_by_employees', 'contract_type')) {
                $table->dropColumn('contract_type');
            }
        });
    }
};
