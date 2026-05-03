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
        if (!Schema::hasColumn('contracts', 'instrument_type')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->enum('instrument_type', [
                    'electronic',
                    'electronic_tax_register',
                    'property_ownership_owner_are_deceased_endowment',
                    'sale_agreement',
                    'economic_cities_authority_suspended',
                    'property_ownership_owner_are_deceased',
                ]);
            });
        }

        if (!Schema::hasColumn('contracts', 'image_instrument')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('image_instrument')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'age_of_the_property')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->integer('age_of_the_property')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'number_of_units_per_floor')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('number_of_units_per_floor')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'image_address')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('image_address')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'latitude')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->decimal('latitude', 11, 8)->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'longitude')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->decimal('longitude', 11, 8)->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'image_instrument_from_the_front')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('image_instrument_from_the_front')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'image_instrument_from_the_back')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('image_instrument_from_the_back')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'Image_from_the_agency')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('Image_from_the_agency')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'copy_power_of_attorney_from_heirs_to_agent')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('copy_power_of_attorney_from_heirs_to_agent')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'Image_inheritance_certificate')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('Image_inheritance_certificate')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'tenant_roles')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->boolean('tenant_roles')->default(0);
            });
        }

        if (!Schema::hasColumn('contracts', 'tenant_role_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                // Keep nullable to avoid breaking existing rows.
                $table->unsignedBigInteger('tenant_role_id')->nullable();
            });
        }

        // Add FK only when referenced table exists.
        if (
            Schema::hasTable('tenant_roles') &&
            Schema::hasColumn('contracts', 'tenant_role_id')
        ) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->foreign('tenant_role_id')
                    ->references('id')
                    ->on('tenant_roles')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('contracts', 'text_additional_terms')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->text('text_additional_terms')->nullable();
            });
        }

        if (!Schema::hasColumn('contracts', 'additional_terms')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->boolean('additional_terms')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            //
        });
    }
};
