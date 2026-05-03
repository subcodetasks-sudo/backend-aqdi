<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500);
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'session_id']);
            $table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_sessions');
    }
};