<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            if (! Schema::hasColumn('real_estates', 'copy_of_the_authorization_or_agency')) {
                $table->string('copy_of_the_authorization_or_agency')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            if (Schema::hasColumn('real_estates', 'copy_of_the_authorization_or_agency')) {
                $table->dropColumn('copy_of_the_authorization_or_agency');
            }
        });
    }
};
