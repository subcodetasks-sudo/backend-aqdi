<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_paid_by_employees', function (Blueprint $table) {
            $table->id();
            $table->string('contract_uuid')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('customer_mobile', 32);
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('contract_uuid');
            $table->index(['employee_id', 'is_paid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_paid_by_employees');
    }
};
