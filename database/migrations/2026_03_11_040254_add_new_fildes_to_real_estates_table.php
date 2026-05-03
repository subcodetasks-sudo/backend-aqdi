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
        if (!Schema::hasColumn('real_estates', 'instrument_type')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->enum('instrument_type', [
                    'electronic',
                    'electronic_tax_register',
                    'property_ownership__owner_are_deceased_endowment',
                    'sale_agreement',
                    'economic_cities_authority_suspended',
                    'property_ownership__owner_are_deceased',
                ]);
            });
        }

        if (!Schema::hasColumn('real_estates', 'image_instrument')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->string('image_instrument')->nullable();
            });
        }

        if (!Schema::hasColumn('real_estates', 'age_of_the_property')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->integer('age_of_the_property')->nullable();
            });
        }

        if (!Schema::hasColumn('real_estates', 'number_of_units_per_floor')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->string('number_of_units_per_floor')->nullable();
            });
        }

        if (!Schema::hasColumn('real_estates', 'image_address')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->string('image_address')->nullable();
            });
        }

        if (!Schema::hasColumn('real_estates', 'latitude')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->decimal('latitude', 11, 8)->nullable();
            });
        }

        if (!Schema::hasColumn('real_estates', 'longitude')) {
            Schema::table('real_estates', function (Blueprint $table) {
                $table->decimal('longitude', 11, 8)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            //
        });
    }
};
