<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (! Schema::hasColumn('employees', 'work_period')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->enum('work_period', ['morning', 'evening'])->default('morning')->after('role_id');
            });

            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE employees MODIFY work_period ENUM('morning', 'evening') NOT NULL DEFAULT 'morning'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'work_period')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('work_period');
        });
    }
};
