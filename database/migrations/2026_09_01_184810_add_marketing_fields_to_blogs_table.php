<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blogs')) {
            return;
        }

        Schema::table('blogs', function (Blueprint $table) {
            if (! Schema::hasColumn('blogs', 'category')) {
                $table->string('category', 64)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('blogs', 'category_label_ar')) {
                $table->string('category_label_ar', 191)->nullable()->after('category');
            }
            if (! Schema::hasColumn('blogs', 'author')) {
                $table->string('author', 191)->nullable()->after('category_label_ar');
            }
            if (! Schema::hasColumn('blogs', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('author');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasColumn('blogs', 'status')) {
            DB::statement("ALTER TABLE blogs MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('blogs')) {
            return;
        }

        Schema::table('blogs', function (Blueprint $table) {
            foreach (['views_count', 'author', 'category_label_ar', 'category'] as $column) {
                if (Schema::hasColumn('blogs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
