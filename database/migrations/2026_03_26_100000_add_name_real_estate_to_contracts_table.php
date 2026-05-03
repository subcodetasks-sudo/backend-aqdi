<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'name_real_estate')) {
                $table->string('name_real_estate')->nullable()->after('name_owner');
            }
        });
    }

    public function down(): void
    
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'name_real_estate')) {
                $table->dropColumn('name_real_estate');
            }
        });
    }
};
