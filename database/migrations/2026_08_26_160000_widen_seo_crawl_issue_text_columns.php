<?php

use App\Support\Migrations\SeoCrawlTables;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SeoCrawlTables::widenExistingColumns();
    }

    public function down(): void
    {
        // Keep TEXT columns; shrinking them would truncate stored crawl messages.
    }
};
