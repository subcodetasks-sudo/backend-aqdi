<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->date('addition_date')->nullable()->after('employee_id');
            $table->date('due_date')->nullable()->after('addition_date');
            $table->decimal('basic_salary', 12, 2)->nullable()->after('due_date');
            $table->decimal('deduction', 12, 2)->default(0)->after('basic_salary');
            $table->decimal('bonus', 12, 2)->default(0)->after('deduction');
            $table->decimal('total', 12, 2)->nullable()->after('bonus');
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn([
                'addition_date',
                'due_date',
                'basic_salary',
                'deduction',
                'bonus',
                'total',
            ]);
        });
    }
};
