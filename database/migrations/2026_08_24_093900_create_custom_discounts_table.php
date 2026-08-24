<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('contract_uuid')->nullable()->index();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->foreignId('coupon_usage_id')->nullable()->constrained('coupon_usages')->nullOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('type', 20);
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_before', 10, 2)->default(0);
            $table->decimal('total_after', 10, 2)->default(0);
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_discounts');
    }
};
