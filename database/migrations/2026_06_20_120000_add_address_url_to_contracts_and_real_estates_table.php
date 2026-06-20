<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'address_url')) {
                $table->string('address_url')->nullable()->after('extra_figure');
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'address_url')) {
                $table->string('address_url')->nullable()->after('extra_figure');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'address_url')) {
                $table->dropColumn('address_url');
            }
        });

        Schema::table('real_estates', function (Blueprint $table) {
            if (Schema::hasColumn('real_estates', 'address_url')) {
                $table->dropColumn('address_url');
            }
        });
    }
};
