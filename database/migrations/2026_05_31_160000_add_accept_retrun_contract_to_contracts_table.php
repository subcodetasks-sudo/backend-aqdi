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

        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'accept_retrun_contract')) {
                $table->boolean('accept_retrun_contract')->default(false);
            }

            if (! Schema::hasColumn('contracts', 'accept_retrun_contract_employee_id')) {
                $table->unsignedBigInteger('accept_retrun_contract_employee_id')->nullable();
            }
        });

        if (
            Schema::hasTable('employees')
            && Schema::hasColumn('contracts', 'accept_retrun_contract_employee_id')
        ) {
            try {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->foreign('accept_retrun_contract_employee_id')
                        ->references('id')
                        ->on('employees')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                // FK may already exist
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'accept_retrun_contract_employee_id')) {
                try {
                    $table->dropForeign(['accept_retrun_contract_employee_id']);
                } catch (\Throwable) {
                    //
                }
                $table->dropColumn('accept_retrun_contract_employee_id');
            }

            if (Schema::hasColumn('contracts', 'accept_retrun_contract')) {
                $table->dropColumn('accept_retrun_contract');
            }
        });
    }
};
