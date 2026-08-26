<?php

use App\Support\Migrations\SeoCrawlTables;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SeoCrawlTables::ensure();
    }

    public function down(): void
    {
        SeoCrawlTables::drop();
    }
};
