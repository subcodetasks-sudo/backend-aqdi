<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_brand')) {
                $table->string('payment_brand')->nullable()->after('payment_method');
            }
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_logs', 'cost')) {
                $table->decimal('cost', 10, 4)->nullable()->after('type');
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'moyasar_mada_percent')) {
                $table->decimal('moyasar_mada_percent', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'moyasar_credit_percent')) {
                $table->decimal('moyasar_credit_percent', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'moyasar_fixed_fee')) {
                $table->decimal('moyasar_fixed_fee', 8, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_brand')) {
                $table->dropColumn('payment_brand');
            }
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sms_logs', 'cost')) {
                $table->dropColumn('cost');
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            foreach (['moyasar_mada_percent', 'moyasar_credit_percent', 'moyasar_fixed_fee'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
