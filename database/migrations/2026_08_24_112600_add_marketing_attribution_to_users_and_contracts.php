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
            $existingColumns = $this->getExistingColumns('contracts');

            Schema::table('contracts', function (Blueprint $table) use ($existingColumns): void {
                if (! in_array('utm_source', $existingColumns)) {
                    $table->text('utm_source')->nullable();
                }

                if (! in_array('utm_medium', $existingColumns)) {
                    $table->text('utm_medium')->nullable();
                }

                if (! in_array('utm_campaign', $existingColumns)) {
                    $table->text('utm_campaign')->nullable();
                }

                if (! in_array('utm_term', $existingColumns)) {
                    $table->text('utm_term')->nullable();
                }

                if (! in_array('utm_content', $existingColumns)) {
                    $table->text('utm_content')->nullable();
                }

                if (! in_array('gclid', $existingColumns)) {
                    $table->text('gclid')->nullable();
                }

                if (! in_array('fbclid', $existingColumns)) {
                    $table->text('fbclid')->nullable();
                }

                if (! in_array('ttclid', $existingColumns)) {
                    $table->text('ttclid')->nullable();
                }

                if (! in_array('twclid', $existingColumns)) {
                    $table->text('twclid')->nullable();
                }

                if (! in_array('sccid', $existingColumns)) {
                    $table->text('sccid')->nullable();
                }

                if (! in_array('attributed_at', $existingColumns)) {
                    $table->timestamp('attributed_at')->nullable();
                }
            });

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