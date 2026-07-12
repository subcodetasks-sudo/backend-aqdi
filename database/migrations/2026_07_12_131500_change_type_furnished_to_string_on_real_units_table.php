<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('real_units') || ! Schema::hasColumn('real_units', 'type_furnished')) {
            return;
        }

        // Allow text or boolean-like values (store as string).
        DB::statement('ALTER TABLE `real_units` MODIFY `type_furnished` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('real_units') || ! Schema::hasColumn('real_units', 'type_furnished')) {
            return;
        }

        DB::table('real_units')
            ->whereNotNull('type_furnished')
            ->whereNotIn('type_furnished', ['0', '1'])
            ->update(['type_furnished' => '0']);

        DB::statement('ALTER TABLE `real_units` MODIFY `type_furnished` TINYINT(1) NOT NULL DEFAULT 0');
    }
};
