<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_alert_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('message_alert_sections', 'type')) {
                $table->string('type', 32)->default('client')->after('sort_order');
                $table->index('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_alert_sections', function (Blueprint $table) {
            if (Schema::hasColumn('message_alert_sections', 'type')) {
                $table->dropIndex(['type']);
                $table->dropColumn('type');
            }
        });
    }
};
