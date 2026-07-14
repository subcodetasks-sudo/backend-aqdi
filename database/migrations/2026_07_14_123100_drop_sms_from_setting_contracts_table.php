<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('setting_contracts', 'sms_user')) {
                $table->dropColumn(['sms_user', 'sms_owner', 'sms_employee']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('setting_contracts', function (Blueprint $table) {
            $table->text('sms_user')->nullable()->after('label');
            $table->text('sms_owner')->nullable()->after('sms_user');
            $table->text('sms_employee')->nullable()->after('sms_owner');
        });
    }
};
