<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'unit_usage_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->unsignedBigInteger('unit_usage_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('real_units')) {
            Schema::table('real_units', function (Blueprint $table) {
                if (! Schema::hasColumn('real_units', 'unit_usage_id')) {
                    $table->foreignId('unit_usage_id')
                        ->nullable()
                        ->constrained('unit_usages')
                        ->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('unit_usage_id')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: reverting nullability could fail when null values exist.
    }
};
