<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paperworks', function (Blueprint $table) {
            if (! Schema::hasColumn('paperworks', 'icon')) {
                $table->string('icon')->nullable()->after('contract_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paperworks', function (Blueprint $table) {
            if (Schema::hasColumn('paperworks', 'icon')) {
                $table->dropColumn('icon');
            }
        });
    }
};
