<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `refundable_contracts.user_id` was referenced by the model ($fillable, user() relation) and by
 * UserController's total_refunded_amount subquery, but no migration ever created the column —
 * every query touching it fails with "Unknown column 'refundable_contracts.user_id'".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('refundable_contracts', 'user_id')) {
            Schema::table('refundable_contracts', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('contract_id')->constrained('users')->nullOnDelete();
            });
        }

        DB::statement(
            'update `refundable_contracts` as rc '.
            'inner join `contracts` as c on c.id = rc.contract_id '.
            'set rc.user_id = c.user_id '.
            'where rc.user_id is null'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('refundable_contracts', 'user_id')) {
            Schema::table('refundable_contracts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
