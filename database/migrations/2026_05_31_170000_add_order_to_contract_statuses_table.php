<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_statuses')) {
            return;
        }

        if (! Schema::hasColumn('contract_statuses', 'order')) {
            Schema::table('contract_statuses', function (Blueprint $table) {
                $table->unsignedInteger('order')->default(0);
            });
        }

        $ids = DB::table('contract_statuses')->orderBy('id')->pluck('id');
        foreach ($ids as $index => $id) {
            DB::table('contract_statuses')->where('id', $id)->update([
                'order' => $index + 1,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contract_statuses') || ! Schema::hasColumn('contract_statuses', 'order')) {
            return;
        }

        Schema::table('contract_statuses', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
