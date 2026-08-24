<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Intentionally a no-op.
 *
 * P&L / marketing totals are project-wide, not stored on each contract.
 * Adding VARCHAR/TEXT UTM columns to `contracts` also hits MySQL error 1118
 * (row size too large). First-touch attribution stays on `users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
