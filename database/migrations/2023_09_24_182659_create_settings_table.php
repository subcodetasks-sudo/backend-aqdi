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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('twitter')->nullable();
            $table->string('snapchat')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('linkedIn')->nullable();
            $table->string('whatsapp_contact')->nullable();
            $table->string('whatsapp_contract')->nullable();
            $table->float('housing_tax')->nullable();
            $table->float('commercial_tax')->nullable();
            $table->float('application_fees')->nullable();
            $table->boolean('open_payment')->default(1);
            $table->string('version')->nullable();
            $table->longText('text_message_user')->nullable();
            $table->longText('text_message_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
