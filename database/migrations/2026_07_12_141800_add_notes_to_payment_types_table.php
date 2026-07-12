<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_types', 'notes')) {
                $table->text('notes')->nullable()->after('contract_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            if (Schema::hasColumn('payment_types', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
