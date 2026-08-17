<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'deed_addition_method')) {
                $table->string('deed_addition_method', 20)->nullable();
            }
            if (! Schema::hasColumn('contracts', 'deed_number')) {
                $table->string('deed_number')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'ejar_contract_number')) {
                $table->string('ejar_contract_number')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'ejar_status_notes')) {
                $table->text('ejar_status_notes')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'status_attachment')) {
                $table->string('status_attachment')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'ejar_contract_draft_number')) {
                $table->string('ejar_contract_draft_number')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'draft_contact_number_mode')) {
                $table->string('draft_contact_number_mode', 20)->nullable();
            }
            if (! Schema::hasColumn('contracts', 'draft_contact_number')) {
                $table->string('draft_contact_number', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        $columns = [
            'deed_addition_method',
            'deed_number',
            'ejar_contract_number',
            'ejar_status_notes',
            'status_attachment',
            'ejar_contract_draft_number',
            'draft_contact_number_mode',
            'draft_contact_number',
        ];

        $existing = array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn('contracts', $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
