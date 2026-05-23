<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

    /**
     * MySQL/MariaDB on shared hosting rebuilds the table on MODIFY/CHANGE;
     * disable FK checks to avoid errno 150 when other tables reference contracts.
     */
    private function withoutForeignKeyChecks(callable $callback): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            $callback();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function contractStartingDateIsString(): bool
    {
        $row = DB::selectOne("
            SELECT DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'contracts'
              AND COLUMN_NAME = 'contract_starting_date'
        ");

        return $row && in_array(strtolower((string) $row->DATA_TYPE), ['varchar', 'char', 'text'], true);
    }

    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

      
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

            $addEnum('type_dob', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_dob_property_owner', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_dob_property_owner_agent', ['hijri', 'gregorian']);
            $addEnum('type_tenant_dob', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_dob_tenant_agent', ['hijri', 'gregorian']);
            $addEnum('type_contract_starting_date', ['hijri', 'gregorian'], 'hijri');
            $addEnum('type_instrument_history', ['hijri', 'gregorian']);
            $addEnum('type_date_first_registration', ['hijri', 'gregorian']);
            $addEnum('type_agency_instrument_date_of_property_owner', ['hijri', 'gregorian']);

            $addInt('split_ac');
            $addInt('window_ac');
            $addBool('kitchen_tank', false);
            $addBool('furnished', false);
            $addString('type_furnished');

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
             
            }
        }

     
        if (Schema::hasColumn('contracts', 'contract_starting_date')
            && ! $this->contractStartingDateIsString()) {
            $this->withoutForeignKeyChecks(function (): void {
                DB::statement('ALTER TABLE `contracts` MODIFY `contract_starting_date` VARCHAR(20) NULL');
            });
        }

  
        if (Schema::hasColumn('contracts', 'tenant_dob_hijri') && ! Schema::hasColumn('contracts', 'tenant_dob')) {
            $this->withoutForeignKeyChecks(function (): void {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->renameColumn('tenant_dob_hijri', 'tenant_dob');
                });
            });
        }

        if (Schema::hasColumn('contracts', 'dob_hijri_of_property_tenant_agent')
            && ! Schema::hasColumn('contracts', 'dob_of_property_tenant_agent')) {
            $this->withoutForeignKeyChecks(function (): void {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->renameColumn('dob_hijri_of_property_tenant_agent', 'dob_of_property_tenant_agent');
                });
            });
        }

   
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

            $this->withoutForeignKeyChecks(function (): void {
                Schema::table('contracts', function (Blueprint $table) {
                    if (Schema::hasColumn('contracts', 'property_owner_dob_hijri')) {
                        $table->dropColumn('property_owner_dob_hijri');
                    }
                    if (Schema::hasColumn('contracts', 'property_owner_dob_gregorian')) {
                        $table->dropColumn('property_owner_dob_gregorian');
                    }
                });
            });
        }


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

        if (Schema::hasColumn('contracts', 'instrument_type')) {
            $this->withoutForeignKeyChecks(function (): void {
                DB::statement('ALTER TABLE `contracts` '.$this->finalInstrumentTypeEnumSql());
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

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

        if (Schema::hasColumn('contracts', 'contract_starting_date')
            && $this->contractStartingDateIsString()) {
            $this->withoutForeignKeyChecks(function (): void {
                DB::statement('ALTER TABLE `contracts` MODIFY `contract_starting_date` DATE NULL');
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
