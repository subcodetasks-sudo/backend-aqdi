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
        Schema::create('contract_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period');
            $table->string('note_ar');
            $table->string('note_en')->nullable();
            $table->enum('contract_type', ['housing', 'commercial']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_periods');
    }
};
