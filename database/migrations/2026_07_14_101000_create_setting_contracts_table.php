<?php

use App\Models\RealEstate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_contracts', function (Blueprint $table) {
            $table->id();
            $table->enum('instrument_type', RealEstate::instrumentTypes())->unique();
            $table->boolean('realestate')->default(false);
            $table->boolean('contract')->default(false);
            $table->string('label')->nullable();
            $table->text('sms_user')->nullable();
            $table->text('sms_owner')->nullable();
            $table->text('sms_employee')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_contracts');
    }
};
