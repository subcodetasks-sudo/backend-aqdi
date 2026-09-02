<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_service_pages')) {
            return;
        }

        Schema::create('marketing_service_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('path', 191)->unique();
            $table->string('target_keyword', 191)->nullable();
            $table->string('status', 32)->default('draft');
            $table->longText('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_service_pages');
    }
};
