<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('real_units', function (Blueprint $table) {
            $table->boolean('kitchen_tank')->default(0);
            $table->boolean('furnished')->default(0);
            $table->boolean('type_furnished')->default(0); // 1 is new   0 is old 
            $table->boolean('electricity_meter')->default(0);
            $table->boolean('water_meter')->default(0);
            
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('real_units', function (Blueprint $table) {
            //
        });
    }
};
