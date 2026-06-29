<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('real_estates') || ! Schema::hasColumn('real_estates', 'property_owner_is_deceased')) {
            return;
        }

        Schema::table('real_estates', function (Blueprint $table) {
            $table->boolean('property_owner_is_deceased')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('real_estates') || ! Schema::hasColumn('real_estates', 'property_owner_is_deceased')) {
            return;
        }

        Schema::table('real_estates', function (Blueprint $table) {
            $table->boolean('property_owner_is_deceased')->default(0)->nullable(false)->change();
        });
    }
};
