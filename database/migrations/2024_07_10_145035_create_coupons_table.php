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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('code_coupon')->unique();
            $table->enum('type_coupon',['ratio','value'])->default('ratio');
            $table->string('value_coupon');
            $table->date('date_start');
            $table->date('date_end');
            $table->integer('usage');
            $table->integer('usage_of_user');
            $table->boolean('is_review')->default(false);  //false is inactive &&  1 is active
            $table->boolean('is_delete')->default(false);  //false is no_delete &&  1 is is_delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
