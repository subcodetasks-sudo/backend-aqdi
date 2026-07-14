<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('sms_user')->nullable()->after('text_message_admin');
            $table->longText('sms_owner')->nullable()->after('sms_user');
            $table->longText('sms_employee')->nullable()->after('sms_owner');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sms_user', 'sms_owner', 'sms_employee']);
        });
    }
};
