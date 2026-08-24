<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $this->addVarcharAttributionColumns($table, 'users');
            });
        }

        // contracts is already near MySQL's 65,535-byte in-row limit, so VARCHAR
        // columns fail with error 1118. TEXT is stored off-page.
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table): void {
                $this->addTextAttributionColumns($table, 'contracts');
            });
            $this->addMysqlPrefixIndexes('contracts');
        }
    }

    public function down(): void
    {
        $drop = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'gclid',
            'fbclid',
            'ttclid',
            'twclid',
            'sccid',
            'attributed_at',
        ];

        foreach (['users', 'contracts'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $this->dropMysqlPrefixIndexes($tableName);

            Schema::table($tableName, function (Blueprint $table) use ($drop, $tableName): void {
                foreach ($drop as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function addVarcharAttributionColumns(Blueprint $table, string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'utm_source')) {
            $table->string('utm_source', 64)->nullable()->index();
        }
        if (! Schema::hasColumn($tableName, 'utm_medium')) {
            $table->string('utm_medium', 64)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'utm_campaign')) {
            $table->string('utm_campaign', 191)->nullable()->index();
        }
        if (! Schema::hasColumn($tableName, 'utm_term')) {
            $table->string('utm_term', 191)->nullable()->index();
        }
        if (! Schema::hasColumn($tableName, 'utm_content')) {
            $table->string('utm_content', 191)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'gclid')) {
            $table->string('gclid', 191)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'fbclid')) {
            $table->string('fbclid', 191)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'ttclid')) {
            $table->string('ttclid', 191)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'twclid')) {
            $table->string('twclid', 191)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'sccid')) {
            $table->string('sccid', 191)->nullable();
        }
        if (! Schema::hasColumn($tableName, 'attributed_at')) {
            $table->timestamp('attributed_at')->nullable();
        }
    }

    private function addTextAttributionColumns(Blueprint $table, string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'utm_source')) {
            $table->text('utm_source')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'utm_medium')) {
            $table->text('utm_medium')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'utm_campaign')) {
            $table->text('utm_campaign')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'utm_term')) {
            $table->text('utm_term')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'utm_content')) {
            $table->text('utm_content')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'gclid')) {
            $table->text('gclid')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'fbclid')) {
            $table->text('fbclid')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'ttclid')) {
            $table->text('ttclid')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'twclid')) {
            $table->text('twclid')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'sccid')) {
            $table->text('sccid')->nullable();
        }
        if (! Schema::hasColumn($tableName, 'attributed_at')) {
            $table->timestamp('attributed_at')->nullable();
        }
    }

    private function addMysqlPrefixIndexes(string $tableName): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->addMysqlPrefixIndex($tableName, 'utm_source', 64);
        $this->addMysqlPrefixIndex($tableName, 'utm_campaign', 191);
        $this->addMysqlPrefixIndex($tableName, 'utm_term', 191);
    }

    private function addMysqlPrefixIndex(string $tableName, string $column, int $length): void
    {
        $index = $tableName.'_'.$column.'_index';
        $exists = DB::select('SHOW INDEX FROM `'.$tableName.'` WHERE Key_name = ?', [$index]);
        if ($exists !== []) {
            return;
        }

        DB::statement("ALTER TABLE `{$tableName}` ADD INDEX `{$index}` (`{$column}`({$length}))");
    }

    private function dropMysqlPrefixIndexes(string $tableName): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['utm_source', 'utm_campaign', 'utm_term'] as $column) {
            $index = $tableName.'_'.$column.'_index';
            $exists = DB::select('SHOW INDEX FROM `'.$tableName.'` WHERE Key_name = ?', [$index]);
            if ($exists === []) {
                continue;
            }

            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$index}`");
        }
    }
};
