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
        Schema::create('real_units', function (Blueprint $table) {
                $table->id();
            $table->timestamps();
        $table->foreignId('real_estates_units_id')->nullable()
            ->constrained(table: 'real_estates', column: 'id')
            ->nullOnDelete();      

            $table->string('unit_area')->nullable();
            $table->enum('unit_usage', [
                'family_residence', 
                'individual_residence', 
                'families', 
                'residential_commercial', 
                'collective_housing'
            ])->nullable();          
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->nullOnDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete('cascade');

            $table->string('number_of_rooms')->nullable();
            $table->string('Gasmeter')->nullable();
            $table->string('floor_number')->nullable();
            $table->string('Number_parking_spaces')->nullable();
            $table->string('unit_number')->nullable();
            $table->string('electricity_meter_number')->nullable();
            $table->string('water_meter_number')->nullable();
            $table->string('number_of_unit_air_conditioners')->nullable();
            $table->string('The_number_of_the_toilet')->nullable();
            $table->string('The_number_of_halls')->nullable();
            $table->string('The_number_of_kitchens')->nullable();
            $table->integer('Services')->defualt(0);//o  is not Services  && 1 is a have Services
 
            $table->integer('is_deleted')->default(0); //o  is not delete  && 1 is a deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_units');
    }
};
