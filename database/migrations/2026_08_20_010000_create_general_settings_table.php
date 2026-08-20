<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label_ar');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = collect(config('general_settings', []))
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                'label_ar' => $definition['label_ar'],
                'enabled' => $definition['default'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('general_settings')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
