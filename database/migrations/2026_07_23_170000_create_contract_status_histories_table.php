<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_status_histories')) {
            return;
        }

        Schema::create('contract_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('status_type', 20)->default('contract'); // contract|draft|system
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('status'); // machine key e.g. received, on_hold
            $table->string('status_label'); // Arabic label from dashboard
            $table->string('status_color')->nullable();
            $table->text('status_description')->nullable();
            $table->string('source', 40)->nullable(); // admin|receive|payment|system
            $table->timestamps();

            $table->index(['contract_id', 'id']);
            $table->index(['contract_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_status_histories');
    }
};
