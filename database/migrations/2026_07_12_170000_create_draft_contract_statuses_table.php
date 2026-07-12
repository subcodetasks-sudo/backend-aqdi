<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('draft_contract_statuses')) {
            Schema::create('draft_contract_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('color');
                $table->string('color_text')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('contract_statuses') && Schema::hasTable('draft_contract_statuses')) {
            $existingNames = DB::table('draft_contract_statuses')->pluck('name')->all();

            $rows = DB::table('contract_statuses')
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                if (in_array($row->name, $existingNames, true)) {
                    continue;
                }

                DB::table('draft_contract_statuses')->insert([
                    'name' => $row->name,
                    'color' => $row->color,
                    'color_text' => $row->color_text,
                    'description' => $row->description,
                    'order' => $row->order ?? 0,
                    'is_active' => (bool) $row->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('contracts') && ! Schema::hasColumn('contracts', 'draft_contract_status_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->foreignId('draft_contract_status_id')
                    ->nullable()
                    ->constrained('draft_contract_statuses')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'draft_contract_status_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('draft_contract_status_id');
            });
        }

        Schema::dropIfExists('draft_contract_statuses');
    }
};
