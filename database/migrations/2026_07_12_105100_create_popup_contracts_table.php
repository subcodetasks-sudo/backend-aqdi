<?php

use App\Models\RealEstate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popup_contracts', function (Blueprint $table) {
            $table->id();
            $table->enum('instrument_type', RealEstate::instrumentTypes());
            $table->boolean('popup_status_contract')->default(false);
            $table->boolean('popup_status_realestate')->default(false);
            $table->text('content_popup')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popup_contracts');
    }
};
