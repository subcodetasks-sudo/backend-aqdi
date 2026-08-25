<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'meter_transfer_fee')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->decimal('meter_transfer_fee', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'meter_transfer_fee')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('meter_transfer_fee');
            });
        }
    }
};
