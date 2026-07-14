<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'duration_preset')) {
                $table->string('duration_preset', 32)->nullable();
            }
            if (! Schema::hasColumn('contracts', 'duration_years')) {
                $table->unsignedTinyInteger('duration_years')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'duration_months')) {
                $table->unsignedTinyInteger('duration_months')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'total_months')) {
                $table->unsignedSmallInteger('total_months')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            foreach (['duration_preset', 'duration_years', 'duration_months', 'total_months'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
