<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'kitchen_tank')) {
                $table->boolean('kitchen_tank')->default(false);
            }

            if (! Schema::hasColumn('contracts', 'furnished')) {
                $table->boolean('furnished')->default(false);
            }

            if (! Schema::hasColumn('contracts', 'type_furnished')) {
                $table->string('type_furnished')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            foreach (['type_furnished', 'furnished', 'kitchen_tank'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

