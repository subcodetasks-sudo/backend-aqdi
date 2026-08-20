<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'moyasar_fee_percent')) {
                $table->decimal('moyasar_fee_percent', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'monthly_salaries')) {
                $table->decimal('monthly_salaries', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'operating_budget')) {
                $table->decimal('operating_budget', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'marketing_budget')) {
                $table->decimal('marketing_budget', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'moyasar_fee_percent',
                'monthly_salaries',
                'operating_budget',
                'marketing_budget',
            ]);
        });
    }
};
