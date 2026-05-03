<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'tenant_role_ids')) {
                $table->json('tenant_role_ids')->nullable()->after('tenant_role_id');
            }
        });

        if (Schema::hasColumn('contracts', 'tenant_role_id') && Schema::hasColumn('contracts', 'tenant_role_ids')) {
            DB::table('contracts')
                ->whereNotNull('tenant_role_id')
                ->whereNull('tenant_role_ids')
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('contracts')->where('id', $row->id)->update([
                            'tenant_role_ids' => json_encode([(int) $row->tenant_role_id]),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'tenant_role_ids')) {
                $table->dropColumn('tenant_role_ids');
            }
        });
    }
};
