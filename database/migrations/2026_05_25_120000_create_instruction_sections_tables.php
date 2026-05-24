<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruction_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title_ar');
            $table->string('description_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('instruction_section_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instruction_section_id')
                ->constrained('instruction_sections')
                ->cascadeOnDelete();
            $table->string('title_ar')->nullable();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('file_extension', 10)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruction_section_images');
        Schema::dropIfExists('instruction_sections');
    }
};
