<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // contracts columns are added in 2026_08_24_140000 (TEXT). Do not add
        // VARCHAR columns here — contracts already hits MySQL error 1118.
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $existingColumns = $this->getExistingColumns('users');
        $columnsToDrop = array_values(array_intersect($this->columns, $existingColumns));
        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columnsToDrop): void {
            $table->dropColumn($columnsToDrop);
        });
    }

    private function getExistingColumns(string $tableName): array
    {
        return array_values(array_filter(
            $this->columns,
            fn (string $column) => Schema::hasColumn($tableName, $column)
        ));
    }
};