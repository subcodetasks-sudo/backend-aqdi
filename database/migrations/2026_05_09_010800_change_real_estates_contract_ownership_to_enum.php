<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * real_estates.contract_ownership was a boolean while APIs and contracts use owner|tenant.
     */
    public function up(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            $table->enum('contract_ownership_new', ['owner', 'tenant'])->nullable();
        });

        foreach (DB::table('real_estates')->select(['id', 'contract_ownership'])->cursor() as $row) {
            $slug = $this->mapLegacyOwnershipToSlug($row->contract_ownership);
            DB::table('real_estates')->where('id', $row->id)->update(['contract_ownership_new' => $slug]);
        }

        Schema::table('real_estates', function (Blueprint $table) {
            $table->dropColumn('contract_ownership');
        });

        Schema::table('real_estates', function (Blueprint $table) {
            $table->renameColumn('contract_ownership_new', 'contract_ownership');
        });
    }

    public function down(): void
    {
        Schema::table('real_estates', function (Blueprint $table) {
            $table->boolean('contract_ownership_old')->nullable()->default(false);
        });

        foreach (DB::table('real_estates')->select(['id', 'contract_ownership'])->cursor() as $row) {
            if ($row->contract_ownership === null) {
                DB::table('real_estates')->where('id', $row->id)->update(['contract_ownership_old' => null]);

                continue;
            }

            DB::table('real_estates')->where('id', $row->id)->update([
                'contract_ownership_old' => $row->contract_ownership === 'owner',
            ]);
        }

        Schema::table('real_estates', function (Blueprint $table) {
            $table->dropColumn('contract_ownership');
        });

        Schema::table('real_estates', function (Blueprint $table) {
            $table->renameColumn('contract_ownership_old', 'contract_ownership');
        });
    }

    private function mapLegacyOwnershipToSlug(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if ($raw === 'owner' || $raw === 'tenant') {
            return $raw;
        }

        $isOwner = $raw === true || $raw === 1 || $raw === '1';

        return $isOwner ? 'owner' : 'tenant';
    }
};
