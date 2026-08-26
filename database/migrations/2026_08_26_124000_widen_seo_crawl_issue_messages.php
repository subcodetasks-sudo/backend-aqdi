<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_crawl_issues')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE seo_crawl_issues MODIFY path TEXT NOT NULL');
        DB::statement('ALTER TABLE seo_crawl_issues MODIFY message_ar TEXT NOT NULL');
        DB::statement('ALTER TABLE seo_crawl_issues MODIFY message_en TEXT NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_crawl_issues')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE seo_crawl_issues MODIFY path VARCHAR(1024) NOT NULL');
        DB::statement('ALTER TABLE seo_crawl_issues MODIFY message_ar VARCHAR(512) NOT NULL');
        DB::statement('ALTER TABLE seo_crawl_issues MODIFY message_en VARCHAR(512) NOT NULL');
    }
};
