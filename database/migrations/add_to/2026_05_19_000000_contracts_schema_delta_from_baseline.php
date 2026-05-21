<?php

/**
 * =============================================================================
 * ملف مرجعي فقط — لا يُشغَّل تلقائياً مع migrate
 * =============================================================================
 *
 * المسار: database/migrations/add_to/
 * Laravel لا يحمّل هذا المجلد افتراضياً (خارج database/migrations الجذر).
 *
 * الغرض: تطبيق كل ما تغيّر في جدول `contracts` في المشروع مقارنةً بـ migration
 * الإنشاء الأصلي (2024_01_30_165201_create_contracts_table.php أو النسخة التي أرسلتها).
 *
 * تشغيل يدوي (عند الحاجة فقط):
 *   php artisan migrate --path=database/migrations/add_to/2026_05_19_000000_contracts_schema_delta_from_baseline.php
 *
 * مصادر التغييرات في المشروع (للمراجعة):
 *   - 2026_03_11_043547_add_new_fildes_to_contracts_table.php
 *   - 2026_03_26_100000_add_name_real_estate_to_contracts_table.php
 *   - 2024_01_30_165203_create_add_expiry_date_column_to_contracts_table.php
 *   - 2026_04_04_000000_change_contract_starting_date_to_string_on_contracts_table.php
 *   - 2026_04_06_131907_add_contract_status_id_to_contracts.php
 *   - 2026_04_08_130000_expand_instrument_type_enum_on_contracts_table.php
 *   - 2026_04_14_113122_add_dob_type_to_contracts_table.php (type_dob)
 *   - 2026_04_15_120000_add_dob_types_to_owner_fields.php
 *   - 2026_04_19_100000_add_calendar_types_for_dates_to_contracts_and_real_estates.php
 *   - 2026_04_24_183750_add_copy_of_the_endowment_registration_certificate_to_contracts_table.php
 *   - 2026_05_03_120000_merge_contract_property_owner_dob_columns.php
 *   - 2026_05_08_163600_sync_contract_instrument_type_enum_with_real_estate.php
 *   - 2026_05_09_120000_add_real_estate_owner_endowment_fields_and_enum.php (enum فقط على contracts)
 *   - 2026_05_09_120000_add_notes_edits_to_contracts_table.php
 *   - 2026_05_09_130000_add_tenant_role_ids_to_contracts_table.php
 *   - 2026_05_11_115827_add_ac_to_contracts_table.php
 *   - 2026_05_11_140000_rename_tenant_dob_hijri_to_tenant_dob_on_contracts_table.php
 *   - 2026_05_11_150000_rename_dob_hijri_of_property_tenant_agent_on_contracts_table.php
 *   - 2026_05_11_160000_add_step5_booleans_to_contracts_table.php
 *
 * ملاحظات أعمدة مستخدمة في الكود بدون migration واضحة في المشروع:
 *   - electricity_meter, water_meter (boolean) — يُحفظان من Contract V2 step5؛ أضفهما يدوياً إن لم يكونا موجودين.
 *   - conditions — يُتحقق منه في Step6Request فقط ولا يُخزَّن كعمود منفصل (يُستخدم other_conditions).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** القيم النهائية لـ instrument_type في المشروع (مايو 2026). */
    private function finalInstrumentTypeEnumSql(): string
    {
        return <<<'SQL'
MODIFY `instrument_type` ENUM(
    'electronic',
    'old_handwritten',
    'strong_argument',
    'electronic_tax_register',
    'property_ownership_owner_are_deceased_endowment',
    'property_ownership_owner_is_endowment',
    'sale_agreement',
    'electronic_deed_from_the_ministry_of_justice',
    'economic_cities_authority_suspended',
    'sublease_agreement',
    'lease_renewal',
    'property_ownership_owner_are_suspended',
    'property_ownership_owner_are_deceased'
) NULL
SQL;
    }

    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        // ---------------------------------------------------------------------
        // 1) أعمدة جديدة (غير موجودة في migration الإنشاء الأصلي)
        // ---------------------------------------------------------------------
        Schema::table('contracts', function (Blueprint $table) {
            $addString = static function (string $col) use ($table): void {
                if (! Schema::hasColumn('contracts', $col)) {
                    $table->string($col)->nullable();
                }
            };
            $addText = static function (string $col) use ($table): void {
                if (! Schema::hasColumn('contracts', $col)) {
                    $table->text($col)->nullable();
                }
            };
            $addBool = static function (string $col, bool $default = false) use ($table): void {
                if (! Schema::hasColumn('contracts', $col)) {
                    $table->boolean($col)->default($default);
                }
            };
            $addInt = static function (string $col) use ($table): void {
                if (! Schema::hasColumn('contracts', $col)) {
                    $table->integer($col)->nullable();
                }
            };
            $addEnum = static function (string $col, array $values, ?string $default = null) use ($table): void {
                if (! Schema::hasColumn('contracts', $col)) {
                    $colDef = $table->enum($col, $values);
                    if ($default !== null) {
                        $colDef->default($default);
                    } else {
                        $colDef->nullable();
                    }
                }
            };

            // موقع / صك / صور
            $addString('image_instrument');
            $addInt('age_of_the_property');
            $addString('number_of_units_per_floor');
            $addString('image_address');
            if (! Schema::hasColumn('contracts', 'latitude')) {
                $table->decimal('latitude', 11, 8)->nullable();
            }
            if (! Schema::hasColumn('contracts', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            $addString('image_instrument_from_the_front');
            $addString('image_instrument_from_the_back');
            $addString('Image_from_the_agency');
            $addString('copy_power_of_attorney_from_heirs_to_agent');
            $addString('Image_inheritance_certificate');

            $addString('name_real_estate');

            // وقف / نظارة
            $addString('copy_of_the_endowment_registration_certificate');
            $addString('copy_of_the_trusteeship_deed');
            $addBool('is_multiple_trusteeship_deed_copy', false);

            // مستأجر / شروط
            $addBool('tenant_roles', false);
            if (! Schema::hasColumn('contracts', 'tenant_role_id')) {
                $table->unsignedBigInteger('tenant_role_id')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'tenant_role_ids')) {
                $table->json('tenant_role_ids')->nullable();
            }
            $addText('text_additional_terms');
            $addBool('additional_terms', false);
            $addText('notes_edits');

            // أنواع التقويم (هجري / ميلادي)
            $addEnum('type_dob', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_dob_property_owner', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_dob_property_owner_agent', ['hijri', 'gregorian']);
            $addEnum('type_tenant_dob', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_dob_tenant_agent', ['hijri', 'gregorian']);
            $addEnum('type_contract_starting_date', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_instrument_history', ['hijri', 'gregorian']);
            $addEnum('type_date_first_registration', ['hijri', 'gregorian']);
            $addEnum('type_agency_instrument_date_of_property_owner', ['hijri', 'gregorian']);

            // تكييف / تأثيث
            $addInt('split_ac');
            $addInt('window_ac');
            $addBool('kitchen_tank', false);
            $addBool('furnished', false);
            $addString('type_furnished');

            // اختياري: مستخدم في API V2 step5 إن لم يكن العمود موجوداً
            $addBool('electricity_meter', false);
            $addBool('water_meter', false);

            if (! Schema::hasColumn('contracts', 'expiry_date')) {
                $table->date('expiry_date')->nullable();
            }

            if (! Schema::hasColumn('contracts', 'contract_status_id')) {
                $table->foreignId('contract_status_id')
                    ->nullable()
                    ->constrained('contract_statuses')
                    ->nullOnDelete();
            }
        });

        // FK لـ tenant_role_id إن وُجد الجدول
        if (
            Schema::hasTable('tenant_roles')
            && Schema::hasColumn('contracts', 'tenant_role_id')
        ) {
            try {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->foreign('tenant_role_id')
                        ->references('id')
                        ->on('tenant_roles')
                        ->cascadeOnDelete();
                });
            } catch (\Throwable) {
                // قد يكون الـ FK موجوداً مسبقاً
            }
        }

        // ---------------------------------------------------------------------
        // 2) تغيير نوع عمود: contract_starting_date من date إلى string
        // ---------------------------------------------------------------------
        if (Schema::hasColumn('contracts', 'contract_starting_date')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->string('contract_starting_date', 20)->nullable()->change();
            });
        }

        // ---------------------------------------------------------------------
        // 3) إعادة تسمية أعمدة (إن وُجدت الأسماء القديمة فقط)
        // ---------------------------------------------------------------------
        if (Schema::hasColumn('contracts', 'tenant_dob_hijri') && ! Schema::hasColumn('contracts', 'tenant_dob')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('tenant_dob_hijri', 'tenant_dob');
            });
        }

        if (Schema::hasColumn('contracts', 'dob_hijri_of_property_tenant_agent')
            && ! Schema::hasColumn('contracts', 'dob_of_property_tenant_agent')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('dob_hijri_of_property_tenant_agent', 'dob_of_property_tenant_agent');
            });
        }

        // ---------------------------------------------------------------------
        // 4) دمج تاريخ ميلاد المالك: عمود واحد property_owner_dob + حذف الهجري/الميلادي
        //    (بدلاً من property_owner_dob_hijri + property_owner_dob_gregorian)
        // ---------------------------------------------------------------------
        if (! Schema::hasColumn('contracts', 'property_owner_dob')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->text('property_owner_dob')->nullable();
            });
        }

        if (Schema::hasColumn('contracts', 'property_owner_dob_hijri')
            || Schema::hasColumn('contracts', 'property_owner_dob_gregorian')) {
            $rows = DB::table('contracts')->select('*')->get();

            foreach ($rows as $row) {
                $type = strtolower(trim((string) (
                    $row->type_dob_property_owner
                        ?? $row->type_dob
                        ?? 'hijri'
                )));

                $value = $type === 'gregorian'
                    ? ($row->property_owner_dob_gregorian ?? null)
                    : ($row->property_owner_dob_hijri ?? null);

                if (($value === null || $value === '') && ! empty($row->property_owner_dob_hijri)) {
                    $value = $row->property_owner_dob_hijri;
                }
                if (($value === null || $value === '') && ! empty($row->property_owner_dob_gregorian)) {
                    $value = $row->property_owner_dob_gregorian;
                }

                if ($value !== null && $value !== '') {
                    DB::table('contracts')->where('id', $row->id)->update([
                        'property_owner_dob' => $value,
                    ]);
                }
            }

            Schema::table('contracts', function (Blueprint $table) {
                if (Schema::hasColumn('contracts', 'property_owner_dob_hijri')) {
                    $table->dropColumn('property_owner_dob_hijri');
                }
                if (Schema::hasColumn('contracts', 'property_owner_dob_gregorian')) {
                    $table->dropColumn('property_owner_dob_gregorian');
                }
            });
        }

        // ---------------------------------------------------------------------
        // 5) ترحيل tenant_role_id → tenant_role_ids (JSON)
        // ---------------------------------------------------------------------
        if (Schema::hasColumn('contracts', 'tenant_role_id') && Schema::hasColumn('contracts', 'tenant_role_ids')) {
            DB::table('contracts')
                ->whereNotNull('tenant_role_id')
                ->whereNull('tenant_role_ids')
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('contracts')->where('id', $row->id)->update([
                            'tenant_role_ids' => json_encode([(int) $row->tenant_role_id]),
                        ]);
                    }
                });
        }

        // ---------------------------------------------------------------------
        // 6) توسيع ENUM لـ instrument_type إلى القائمة النهائية
        // ---------------------------------------------------------------------
        if (Schema::hasColumn('contracts', 'instrument_type')) {
            DB::statement('ALTER TABLE `contracts` '.$this->finalInstrumentTypeEnumSql());
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        // استرجاع instrument_type إلى القيم الأصلية الثلاث
        if (Schema::hasColumn('contracts', 'instrument_type')) {
            DB::statement("
                ALTER TABLE `contracts`
                MODIFY `instrument_type` ENUM(
                    'electronic',
                    'old_handwritten',
                    'strong_argument'
                ) NULL
            ");
        }

        // استرجاع أعمدة تاريخ المالك المنفصلة
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'property_owner_dob_hijri')) {
                $table->string('property_owner_dob_hijri')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'property_owner_dob_gregorian')) {
                $table->string('property_owner_dob_gregorian')->nullable();
            }
        });

        if (Schema::hasColumn('contracts', 'property_owner_dob')) {
            $rows = DB::table('contracts')->select('*')->get();
            foreach ($rows as $row) {
                $type = strtolower(trim((string) (
                    $row->type_dob_property_owner ?? $row->type_dob ?? 'hijri'
                )));
                $dob = $row->property_owner_dob ?? null;
                DB::table('contracts')->where('id', $row->id)->update([
                    'property_owner_dob_hijri' => $type !== 'gregorian' ? $dob : null,
                    'property_owner_dob_gregorian' => $type === 'gregorian' ? $dob : null,
                ]);
            }
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('property_owner_dob');
            });
        }

        if (Schema::hasColumn('contracts', 'tenant_dob') && ! Schema::hasColumn('contracts', 'tenant_dob_hijri')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('tenant_dob', 'tenant_dob_hijri');
            });
        }

        if (Schema::hasColumn('contracts', 'dob_of_property_tenant_agent')
            && ! Schema::hasColumn('contracts', 'dob_hijri_of_property_tenant_agent')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->renameColumn('dob_of_property_tenant_agent', 'dob_hijri_of_property_tenant_agent');
            });
        }

        if (Schema::hasColumn('contracts', 'contract_starting_date')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->date('contract_starting_date')->nullable()->change();
            });
        }

        Schema::table('contracts', function (Blueprint $table) {
            foreach ([
                'contract_status_id',
                'notes_edits',
                'tenant_role_ids',
                'type_furnished',
                'furnished',
                'kitchen_tank',
                'window_ac',
                'split_ac',
                'water_meter',
                'electricity_meter',
                'is_multiple_trusteeship_deed_copy',
                'copy_of_the_trusteeship_deed',
                'copy_of_the_endowment_registration_certificate',
                'type_agency_instrument_date_of_property_owner',
                'type_date_first_registration',
                'type_instrument_history',
                'type_contract_starting_date',
                'type_dob_tenant_agent',
                'type_tenant_dob',
                'type_dob_property_owner_agent',
                'type_dob_property_owner',
                'type_dob',
                'additional_terms',
                'text_additional_terms',
                'tenant_role_id',
                'tenant_roles',
                'Image_inheritance_certificate',
                'copy_power_of_attorney_from_heirs_to_agent',
                'Image_from_the_agency',
                'image_instrument_from_the_back',
                'image_instrument_from_the_front',
                'longitude',
                'latitude',
                'image_address',
                'number_of_units_per_floor',
                'age_of_the_property',
                'image_instrument',
                'name_real_estate',
                'expiry_date',
            ] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
