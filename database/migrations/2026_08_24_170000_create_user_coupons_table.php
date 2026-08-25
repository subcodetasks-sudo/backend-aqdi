<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('type', 20);
            $table->decimal('value', 10, 2)->default(0);
            $table->string('applies_to', 20)->default('all');
            $table->date('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('notify_on_login')->default(true);
            $table->text('notification_message')->nullable();
            $table->timestamp('login_notified_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
    }
};
