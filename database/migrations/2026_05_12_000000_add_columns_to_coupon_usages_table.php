<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original create_coupon_usages migration left business columns commented out,
     * while CouponUsage / CouponController expect user_id, coupon_id, used_at, contract_uuid.
     */
    public function up(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            if (! Schema::hasColumn('coupon_usages', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('coupon_usages', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('coupon_usages', 'used_at')) {
                $table->timestamp('used_at')->nullable();
            }
            if (! Schema::hasColumn('coupon_usages', 'contract_uuid')) {
                $table->string('contract_uuid')->nullable();
                $table->index('contract_uuid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            if (Schema::hasColumn('coupon_usages', 'contract_uuid')) {
                $table->dropIndex(['contract_uuid']);
            }
            if (Schema::hasColumn('coupon_usages', 'user_id')) {
                $table->dropForeign(['user_id']);
            }
            if (Schema::hasColumn('coupon_usages', 'coupon_id')) {
                $table->dropForeign(['coupon_id']);
            }
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $drop = array_filter([
                Schema::hasColumn('coupon_usages', 'user_id') ? 'user_id' : null,
                Schema::hasColumn('coupon_usages', 'coupon_id') ? 'coupon_id' : null,
                Schema::hasColumn('coupon_usages', 'used_at') ? 'used_at' : null,
                Schema::hasColumn('coupon_usages', 'contract_uuid') ? 'contract_uuid' : null,
            ]);
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
