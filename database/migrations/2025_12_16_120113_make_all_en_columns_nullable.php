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
        // Make all _en columns nullable across all tables
        
        // unit_usages table
        if (Schema::hasTable('unit_usages') && Schema::hasColumn('unit_usages', 'name_en')) {
        Schema::table('unit_usages', function (Blueprint $table) {
            $table->string('name_en')->nullable()->change();
        });
        }

        // unit_types table
        if (Schema::hasTable('unit_types') && Schema::hasColumn('unit_types', 'name_en')) {
            Schema::table('unit_types', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // regions table
        if (Schema::hasTable('regions') && Schema::hasColumn('regions', 'name_en')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // cities table
        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'name_en')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // rea_estat_types table
        if (Schema::hasTable('rea_estat_types') && Schema::hasColumn('rea_estat_types', 'name_en')) {
            Schema::table('rea_estat_types', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // rea_estat_usages table
        if (Schema::hasTable('rea_estat_usages') && Schema::hasColumn('rea_estat_usages', 'name_en')) {
            Schema::table('rea_estat_usages', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // payment_types table
        if (Schema::hasTable('payment_types') && Schema::hasColumn('payment_types', 'name_en')) {
            Schema::table('payment_types', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // paperworks table
        if (Schema::hasTable('paperworks') && Schema::hasColumn('paperworks', 'name_en')) {
            Schema::table('paperworks', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // services_pricings table
        if (Schema::hasTable('services_pricings') && Schema::hasColumn('services_pricings', 'name_en')) {
            Schema::table('services_pricings', function (Blueprint $table) {
                $table->string('name_en')->nullable()->change();
            });
        }

        // contract_periods table
        if (Schema::hasTable('contract_periods') && Schema::hasColumn('contract_periods', 'note_en')) {
            Schema::table('contract_periods', function (Blueprint $table) {
                $table->string('note_en')->nullable()->change();
            });
        }

        // bank_accounts table
        if (Schema::hasTable('bank_accounts')) {
            if (Schema::hasColumn('bank_accounts', 'bank_name_en')) {
                Schema::table('bank_accounts', function (Blueprint $table) {
                    $table->string('bank_name_en')->nullable()->change();
                });
            }
            if (Schema::hasColumn('bank_accounts', 'bank_account_name_en')) {
                Schema::table('bank_accounts', function (Blueprint $table) {
                    $table->string('bank_account_name_en')->nullable()->change();
                });
            }
        }

        // questions table
        if (Schema::hasTable('questions')) {
            if (Schema::hasColumn('questions', 'title_en')) {
                Schema::table('questions', function (Blueprint $table) {
                    $table->string('title_en')->nullable()->change();
                });
            }
            if (Schema::hasColumn('questions', 'answer_en')) {
                Schema::table('questions', function (Blueprint $table) {
                    $table->string('answer_en')->nullable()->change();
                });
            }
        }

        // pages table
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'description_en')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->longText('description_en')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all _en columns to not nullable (if needed)
        // Note: This might fail if there are NULL values in the database
        
        if (Schema::hasTable('unit_usages') && Schema::hasColumn('unit_usages', 'name_en')) {
        Schema::table('unit_usages', function (Blueprint $table) {
            $table->string('name_en')->nullable(false)->change();
        });
        }
    }
};
