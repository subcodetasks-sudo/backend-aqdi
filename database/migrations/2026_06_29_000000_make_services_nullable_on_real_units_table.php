<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('real_units') || ! Schema::hasColumn('real_units', 'Services')) {
            return;
        }

        Schema::table('real_units', function (Blueprint $table) {
            $table->integer('Services')->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('real_units') || ! Schema::hasColumn('real_units', 'Services')) {
            return;
        }

        Schema::table('real_units', function (Blueprint $table) {
            $table->integer('Services')->nullable(false)->default(0)->change();
        });
    }
};
