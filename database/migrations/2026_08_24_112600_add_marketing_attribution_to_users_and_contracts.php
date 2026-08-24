<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
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

    public function up(): void
    {
        if (Schema::hasTable('users')) {
            $existingColumns = $this->getExistingColumns('users');

            Schema::table('users', function (Blueprint $table) use ($existingColumns): void {
                if (! in_array('utm_source', $existingColumns)) {
                    $table->string('utm_source', 64)->nullable()->index();
                }

                if (! in_array('utm_medium', $existingColumns)) {
                    $table->string('utm_medium', 64)->nullable();
                }

                if (! in_array('utm_campaign', $existingColumns)) {
                    $table->string('utm_campaign', 191)->nullable()->index();
                }

                if (! in_array('utm_term', $existingColumns)) {
                    $table->string('utm_term', 191)->nullable()->index();
                }

                if (! in_array('utm_content', $existingColumns)) {
                    $table->string('utm_content', 191)->nullable();
                }

                if (! in_array('gclid', $existingColumns)) {
                    $table->string('gclid', 191)->nullable();
                }

                if (! in_array('fbclid', $existingColumns)) {
                    $table->string('fbclid', 191)->nullable();
                }

                if (! in_array('ttclid', $existingColumns)) {
                    $table->string('ttclid', 191)->nullable();
                }

                if (! in_array('twclid', $existingColumns)) {
                    $table->string('twclid', 191)->nullable();
                }

                if (! in_array('sccid', $existingColumns)) {
                    $table->string('sccid', 191)->nullable();
                }

                if (! in_array('attributed_at', $existingColumns)) {
                    $table->timestamp('attributed_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('contracts')) {
            $this->addContractTextColumns();
            $this->addMysqlPrefixIndexes('contracts');
        }
    }

    public function down(): void
    {
        foreach (['users', 'contracts'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $this->dropMysqlPrefixIndexes($tableName);

            $existingColumns = $this->getExistingColumns($tableName);

            $columnsToDrop = array_values(
                array_intersect($this->columns, $existingColumns)
            );

            if (empty($columnsToDrop)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * contracts is already at MySQL's 65,535-byte in-row limit, so VARCHAR fails
     * (error 1118). TEXT is stored off-page. Use raw SQL so this cannot become VARCHAR.
     */
    private function addContractTextColumns(): void
    {
        $existingColumns = $this->getExistingColumns('contracts');
        $definitions = [
            'utm_source' => 'TEXT NULL',
            'utm_medium' => 'TEXT NULL',
            'utm_campaign' => 'TEXT NULL',
            'utm_term' => 'TEXT NULL',
            'utm_content' => 'TEXT NULL',
            'gclid' => 'TEXT NULL',
            'fbclid' => 'TEXT NULL',
            'ttclid' => 'TEXT NULL',
            'twclid' => 'TEXT NULL',
            'sccid' => 'TEXT NULL',
            'attributed_at' => 'TIMESTAMP NULL',
        ];

        $parts = [];
        foreach ($definitions as $column => $definition) {
            if (! in_array($column, $existingColumns, true)) {
                $parts[] = "ADD `{$column}` {$definition}";
            }
        }

        if ($parts === []) {
            return;
        }

        DB::statement('ALTER TABLE `contracts` '.implode(', ', $parts));
    }

    private function getExistingColumns(string $tableName): array
    {
        return array_filter(
            $this->columns,
            fn (string $column) => Schema::hasColumn($tableName, $column)
        );
    }

    private function addMysqlPrefixIndexes(string $tableName): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addMysqlPrefixIndex($tableName, 'utm_source', 64);
        $this->addMysqlPrefixIndex($tableName, 'utm_campaign', 191);
        $this->addMysqlPrefixIndex($tableName, 'utm_term', 191);
    }

    private function addMysqlPrefixIndex(
        string $tableName,
        string $column,
        int $length
    ): void {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        $indexName = "{$tableName}_{$column}_index";

        $exists = DB::select(
            "SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?",
            [$indexName]
        );

        if (! empty($exists)) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$tableName}`
             ADD INDEX `{$indexName}` (`{$column}`({$length}))"
        );
    }

    private function dropMysqlPrefixIndexes(string $tableName): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['utm_source', 'utm_campaign', 'utm_term'] as $column) {
            $indexName = "{$tableName}_{$column}_index";

            $exists = DB::select(
                "SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?",
                [$indexName]
            );

            if (empty($exists)) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`"
            );
        }
    }
};