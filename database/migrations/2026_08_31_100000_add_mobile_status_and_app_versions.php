<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_settings')) {
            $exists = DB::table('general_settings')->where('key', 'mobile_status')->exists();
            if (! $exists) {
                $now = now();
                DB::table('general_settings')->insert([
                    'key' => 'mobile_status',
                    'label_ar' => 'حالة التطبيق',
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('app_versions')) {
            Schema::create('app_versions', function (Blueprint $table) {
                $table->id();
                $table->string('platform', 32)->unique();
                $table->string('latest_version', 50)->nullable();
                $table->string('min_version', 50)->nullable();
                $table->boolean('force_update')->default(false);
                $table->string('store_url', 500)->nullable();
                $table->text('message_ar')->nullable();
                $table->text('message_en')->nullable();
                $table->timestamps();
            });

            $now = now();
            DB::table('app_versions')->insert([
                [
                    'platform' => 'ios',
                    'latest_version' => null,
                    'min_version' => null,
                    'force_update' => false,
                    'store_url' => null,
                    'message_ar' => 'يرجى تحديث التطبيق لمتابعة الاستخدام',
                    'message_en' => 'Please update the app to continue',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'platform' => 'android',
                    'latest_version' => null,
                    'min_version' => null,
                    'force_update' => false,
                    'store_url' => null,
                    'message_ar' => 'يرجى تحديث التطبيق لمتابعة الاستخدام',
                    'message_en' => 'Please update the app to continue',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');

        if (Schema::hasTable('general_settings')) {
            DB::table('general_settings')->where('key', 'mobile_status')->delete();
        }
    }
};
