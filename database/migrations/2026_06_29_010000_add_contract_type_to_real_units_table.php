<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('real_units')) {
            return;
        }

        if (Schema::hasColumn('real_units', 'contract_type')) {
            return;
        }

        Schema::table('real_units', function (Blueprint $table) {
            $table->enum('contract_type', ['housing', 'commercial'])->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('real_units') || ! Schema::hasColumn('real_units', 'contract_type')) {
            return;
        }

        Schema::table('real_units', function (Blueprint $table) {
            $table->dropColumn('contract_type');
        });
    }
};
