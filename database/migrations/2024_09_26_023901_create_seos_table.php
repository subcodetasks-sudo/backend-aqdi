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
        Schema::create('seos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');   
            $table->string('mobile');
            $table->string('password');
            $table->string('email')->unique();
            $table->boolean('is_seo')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seos');
    }
};
