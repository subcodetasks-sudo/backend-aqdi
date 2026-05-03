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
        Schema::create('real_estates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->boolean('add_legal_agent_of_owner')->default('0')->nullable();
            $table->string('id_num_of_property_owner_agent')->nullable();
            $table->string('dob_of_property_owner_agent')->nullable();
            $table->string('mobile_of_property_owner_agent')->nullable();
            $table->string('agency_number_in_instrument_of_property_owner')->nullable();
            $table->date('agency_instrument_date_of_property_owner')->nullable();
            $table->boolean('property_owner_is_deceased')->default(0); 
            $table->boolean('contract_ownership')->default(0); 
            $table->string('unit_number')->nullable();
            $table->enum('instrument_type', ['electronic', 'strong_argument'])->nullable();
            $table->enum('contract_type', [ 'commercial', 'housing'])->nullable();
            $table->string('date_first_registration')->nullable();
            $table->string('real_estate_registry_number')->nullable();
            $table->string('dob_hijri')->nullable();
            $table->string('instrument_number')->nullable();
            $table->string('instrument_history')->nullable();
            $table->string('name_owner')->nullable();
            $table->string('national_num')->nullable();
            $table->string('number_of_units_in_realestate')->nullable();

            $table->string('DOB')->nullable();
            $table->string('mobile')->nullable();
            $table->string('iban_bank')->nullable();
            $table->string('name_real_estate')->nullable();
            $table->string('number_of_floors')->nullable();
            $table->foreignId('property_type_id')->nullable()->constrained('rea_estat_types')->nullOnDelete('cascade');
            $table->foreignId('property_usages_id')->nullable()->constrained('rea_estat_usages')->nullOnDelete('cascade');
            $table->string('type_real_estate_other')->nullable();
            $table->foreignId('property_city_id')->nullable()->constrained('cities')->nullOnDelete('cascade');
            $table->foreignId('property_place_id')->nullable()->constrained('regions')->nullOnDelete('cascade');
            $table->string('street')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('building_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('extra_figure')->nullable();  
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete('cascade');
            $table->integer('step')->default(0);
            $table->integer('is_deleted')->default(0); //o  is not delete  && 1 is a deleted

 


           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_estates');
    }
};
