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
        Schema::create('contracts', function (Blueprint $table) {

            // $table->id();
            $table->id();

            // start
            $table->uuid();
            $table->enum('contract_type', ['housing', 'commercial']);


            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // step 1
            $table->enum('contract_ownership', ['owner', 'tenant'])->nullable();
            $table->enum('instrument_type', ['electronic', 'old_handwritten', 'strong_argument'])->nullable();
            $table->enum('status', ['retrieved', 'cancel'])->nullable();

            $table->string('instrument_number')->nullable();
            $table->date('instrument_history')->nullable();
        
            $table->string('date_first_registration')->nullable();
            $table->string('real_estate_registry_number')->nullable();
             $table->string('number_of_units_in_realestate')->nullable();
            $table->boolean('property_owner_is_deceased')->default(0); 
             $table->foreignId('property_usages_id')->nullable()->constrained('rea_estat_usages')->onDelete('cascade');

            $table->foreignId('property_city_id')->nullable()->constrained('cities')->onDelete('cascade');
            $table->foreignId('property_place_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('property_type_id')->nullable()->constrained('rea_estat_types')->onDelete('cascade');
          
            $table->string('neighborhood')->nullable();
            $table->string('building_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('extra_figure')->nullable();
            $table->string('number_of_floors')->nullable();
            $table->string('street')->nullable();

            // step 2
            $table->string('property_owner_id_num')->nullable();
            $table->string('property_owner_dob_gregorian')->nullable();
            $table->string('property_owner_dob_hijri')->nullable();
            $table->string('property_owner_mobile')->nullable();
            $table->string('property_owner_iban')->nullable();

            $table->boolean('add_legal_agent_of_owner')->nullable();
            $table->string('id_num_of_property_owner_agent')->nullable();
            $table->date('dob_gregorian_of_property_owner_agent')->nullable();
            $table->string('dob_hijri_of_property_owner_agent')->nullable();
            $table->string('mobile_of_property_owner_agent')->nullable();
            $table->string('agency_number_in_instrument_of_property_owner')->nullable();
            $table->date('agency_instrument_date_of_property_owner')->nullable();
            $table->string('agent_iban_of_property_owner')->nullable();

            $table->string('tenant_id_num')->nullable();
            $table->date('tenant_dob_gregorian')->nullable();
            $table->string('tenant_dob')->nullable();
            $table->string('tenant_mobile')->nullable();
            $table->string('name_owner')->nullable();

            $table->boolean('add_legal_agent_of_tenant')->nullable();
            $table->string('id_num_of_property_tenant_agent')->nullable();
            $table->date('dob_gregorian_of_property_tenant_agent')->nullable();
            $table->string('dob_of_property_tenant_agent')->nullable();
            $table->string('mobile_of_property_tenant_agent')->nullable();
            $table->string('agency_number_in_instrument_of_property_tenant')->nullable();
            $table->date('agency_instrument_date_of_property_tenant')->nullable();

            $table->enum('tenant_entity', ['person', 'institution'])->nullable();
            $table->string('tenant_entity_unified_registry_number')->nullable();
            $table->foreignId('tenant_entity_region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('tenant_entity_city_id')->nullable()->constrained('cities')->onDelete('cascade');

            $table->enum('authorization_type', ['owner_and_representative_of_record', 'agent_for_the_tenant'])->nullable();
            $table->string('copy_of_the_authorization_or_agency')->nullable();
            $table->string('copy_of_the_owner_record')->nullable();
            $table->foreignId('city_of_the_tenant_legal_agent')->nullable()->constrained('cities')->onDelete('cascade');
            $table->foreignId('region_of_the_tenant_legal_agent')->nullable()->constrained('regions')->onDelete('cascade');
            // step 3
            $table->string('unit_number')->nullable();
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->onDelete('cascade');
            $table->string('tootal_rooms')->nullable();
            $table->string('floor_number')->nullable();
            $table->string('unit_area')->nullable();
            $table->string('electricity_meter_number')->nullable();
            $table->string('water_meter_number')->nullable();
            $table->string('number_of_unit_air_conditioners')->nullable();

            // step 4
            $table->date('contract_starting_date')->nullable();
            $table->foreignId('contract_term_in_years')->nullable()->constrained('contract_periods')->onDelete('cascade');
            $table->string('annual_rent_amount_for_the_unit')->nullable();
            $table->foreignId('payment_type_id')->nullable()->constrained('payment_types')->onDelete('cascade');
            $table->string('daily_fine')->nullable();
            $table->boolean('sub_delay')->nullable();
            $table->text('other_conditions')->nullable();
            $table->boolean('premium_membership_for_free')->nullable();
            $table->string('deposit')->nullable();
            $table->string('Guarantee_amount')->nullable();
            $table->foreignId('contract_period_id')->nullable()->constrained('contract_periods')->onDelete('cascade');
            $table->foreignId('real_id')->nullable()->constrained('real_estates')->onDelete('cascade');
            $table->foreignId('real_units_id')->nullable()->constrained('real_units')->onDelete('cascade');
            $table->foreignId('unit_usage_id')->nullable()->constrained('unit_usages')->onDelete('cascade');

            // step 5
            // $table->string('receipt_image')->nullable();
            $table->string('client_account_holder_name')->nullable();
            $table->string('draft_before_paid')->nullable();
            $table->string('draft_after_paid')->nullable();
            $table->string('bank_account_number')->nullable();

            $table->string('The_number_of_the_toilet')->nullable();
            $table->string('The_number_of_halls')->nullable();
            $table->string('The_number_of_kitchens')->nullable();
            $table->string('Gasmeter')->nullable();
            $table->string('Number_parking_spaces')->nullable();
            // step 6
            $table->integer('rating')->nullable();
            $table->text('rating_note')->nullable();
            $table->boolean('Services')->default(0);
            // steps
            $table->integer('step')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->boolean('is_real')->default(false);
            $table->string('file')->nullable();;
            $table->boolean('is_review')->default(false);
            $table->longText('notes')->nullable();
            $table->enum('app_or_web', ['app', 'web']);

             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};