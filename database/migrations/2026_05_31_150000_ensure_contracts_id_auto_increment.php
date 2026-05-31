<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function columnExtra(string $column): ?string
    {
        $row = DB::selectOne('
            SELECT EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ', ['contracts', $column]);

        return $row ? (string) $row->EXTRA : null;
    }

    private function withoutForeignKeyChecks(callable $callback): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            $callback();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function up(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasColumn('contracts', 'id')) {
            return;
        }

        $idExtra = strtolower($this->columnExtra('id') ?? '');

        if (! str_contains($idExtra, 'auto_increment')) {
            $this->withoutForeignKeyChecks(function (): void {
                $maxId = (int) (DB::table('contracts')->max('id') ?? 0);

                DB::statement('ALTER TABLE `contracts` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

                if ($maxId > 0) {
                    DB::statement('ALTER TABLE `contracts` AUTO_INCREMENT = '.($maxId + 1));
                }
            });
        }

        if (Schema::hasColumn('contracts', 'app_or_web')) {
            $this->withoutForeignKeyChecks(function (): void {
                DB::statement("ALTER TABLE `contracts` MODIFY `app_or_web` ENUM('app','web') NOT NULL DEFAULT 'app'");
            });
        }
    }

    public function down(): void
    {
        // Irreversible safely — restoring a broken non-auto-increment id is not desired.
    }
};
