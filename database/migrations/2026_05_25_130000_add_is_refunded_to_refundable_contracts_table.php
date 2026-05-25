<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refundable_contracts', function (Blueprint $table) {
            $table->boolean('is_refunded')->default(false)->after('admin_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('refundable_contracts', function (Blueprint $table) {
            $table->dropColumn('is_refunded');
        });
    }
};
