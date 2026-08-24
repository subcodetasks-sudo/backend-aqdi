<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = function (Blueprint $table): void {
            if (! Schema::hasColumn($table->getTable(), 'utm_source')) {
                $table->string('utm_source', 64)->nullable()->index();
            }
            if (! Schema::hasColumn($table->getTable(), 'utm_medium')) {
                $table->string('utm_medium', 64)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'utm_campaign')) {
                $table->string('utm_campaign', 191)->nullable()->index();
            }
            if (! Schema::hasColumn($table->getTable(), 'utm_term')) {
                $table->string('utm_term', 191)->nullable()->index();
            }
            if (! Schema::hasColumn($table->getTable(), 'utm_content')) {
                $table->string('utm_content', 191)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'gclid')) {
                $table->string('gclid', 191)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'fbclid')) {
                $table->string('fbclid', 191)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'ttclid')) {
                $table->string('ttclid', 191)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'twclid')) {
                $table->string('twclid', 191)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'sccid')) {
                $table->string('sccid', 191)->nullable();
            }
            if (! Schema::hasColumn($table->getTable(), 'attributed_at')) {
                $table->timestamp('attributed_at')->nullable();
            }
        };

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                $columns($table);
            });
        }

        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) use ($columns): void {
                $columns($table);
            });
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

            Schema::table($tableName, function (Blueprint $table) use ($drop, $tableName): void {
                foreach ($drop as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
