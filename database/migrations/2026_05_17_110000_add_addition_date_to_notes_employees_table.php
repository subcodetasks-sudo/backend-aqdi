<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes_employees', function (Blueprint $table) {
            $table->date('addition_date')->nullable()->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('notes_employees', function (Blueprint $table) {
            $table->dropColumn('addition_date');
        });
    }
};
