<?php
// database/migrations/xxxx_xx_xx_create_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->timestamps();
            
            $table->index('log_name');
            $table->index('subject_type');
            $table->index('subject_id');
            $table->index('causer_type');
            $table->index('causer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};

  