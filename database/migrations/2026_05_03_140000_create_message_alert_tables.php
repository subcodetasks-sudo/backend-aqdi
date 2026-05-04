<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates message alert domain tables:
 * - message_alert_sections (groups; audience via type: client | employee)
 * - message_alert_section_items (rows under a section)
 * - message_alerts (text linked to one item)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_alert_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('type', 32)->default('client');
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('message_alert_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_alert_section_id')
                ->constrained('message_alert_sections')
                ->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('message_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_alert_section_item_id')
                ->constrained('message_alert_section_items')
                ->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_alerts');
        Schema::dropIfExists('message_alert_section_items');
        Schema::dropIfExists('message_alert_sections');
    }
};
