<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Permission name (e.g., 'completed_whatsapp_request_view')
            $table->string('section');
            $table->string('section_en')->nullable(); // English section name
            $table->enum('action', ['view', 'create', 'edit', 'delete', 'retrieve']); // Action type
            $table->string('action_label_ar');
            $table->string('action_label_en')->nullable(); // English label
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
