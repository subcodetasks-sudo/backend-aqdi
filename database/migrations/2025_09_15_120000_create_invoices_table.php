<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sequence')->unique();
            $table->string('invoice_number')->unique();
            $table->string('order_number')->nullable();
            $table->date('date');
            $table->string('customer_phone')->nullable();
            $table->text('description')->nullable();
            $table->decimal('rental_fees', 10, 2)->default(0);
            $table->decimal('service_fees', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->index(['contract_id', 'created_by_employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};





