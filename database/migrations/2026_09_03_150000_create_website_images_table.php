<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_images')) {
            Schema::create('website_images', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('label_ar');
                $table->string('label_en')->nullable();
                $table->string('path', 500)->nullable();
                $table->string('static_path', 500)->nullable();
                $table->string('alt_ar', 255)->nullable();
                $table->string('alt_en', 255)->nullable();
                $table->string('meta_title_ar', 255)->nullable();
                $table->string('meta_title_en', 255)->nullable();
                $table->text('meta_description_ar')->nullable();
                $table->text('meta_description_en')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('blogs') && ! Schema::hasColumn('blogs', 'image_alt')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->string('image_alt', 255)->nullable()->after('image');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('website_images');

        if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'image_alt')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropColumn('image_alt');
            });
        }
    }
};
