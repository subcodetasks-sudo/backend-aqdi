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

    /**
     * Add one column per ALTER to avoid MySQL errno 1118 (row size too large).
     * TEXT paths do not count fully toward the 65535 row limit like VARCHAR(255).
     */
    private function addContractsColumnIfMissing(string $column, string $definition): void
    {
        if (Schema::hasColumn('contracts', $column)) {
            return;
        }

        DB::statement("ALTER TABLE `contracts` ADD COLUMN `{$column}` {$definition}");
    }

    private function addNewContractsColumns(): void
    {
        $pathText = 'TEXT NULL';
        $varchar100 = 'VARCHAR(100) NULL';
        $boolFalse = 'TINYINT(1) NOT NULL DEFAULT 0';

        // مسارات ملفات — TEXT لتقليل حجم الصف
        foreach ([
            'image_instrument',
            'image_address',
            'image_instrument_from_the_front',
            'image_instrument_from_the_back',
            'Image_from_the_agency',
            'copy_power_of_attorney_from_heirs_to_agent',
            'Image_inheritance_certificate',
            'copy_of_the_endowment_registration_certificate',
            'copy_of_the_trusteeship_deed',
        ] as $col) {
            $this->addContractsColumnIfMissing($col, $pathText);
        }

        $this->addContractsColumnIfMissing('age_of_the_property', 'INT NULL');
        $this->addContractsColumnIfMissing('number_of_units_per_floor', $varchar100);
        $this->addContractsColumnIfMissing('latitude', 'DECIMAL(11,8) NULL');
        $this->addContractsColumnIfMissing('longitude', 'DECIMAL(11,8) NULL');
        $this->addContractsColumnIfMissing('name_real_estate', $varchar100);
        $this->addContractsColumnIfMissing('is_multiple_trusteeship_deed_copy', $boolFalse);
        $this->addContractsColumnIfMissing('tenant_roles', $boolFalse);
        $this->addContractsColumnIfMissing('tenant_role_id', 'BIGINT UNSIGNED NULL');
        $this->addContractsColumnIfMissing('tenant_role_ids', 'JSON NULL');
        $this->addContractsColumnIfMissing('text_additional_terms', 'TEXT NULL');
        $this->addContractsColumnIfMissing('additional_terms', $boolFalse);
        $this->addContractsColumnIfMissing('notes_edits', 'TEXT NULL');

        $hijriGregorian = "ENUM('hijri','gregorian')";
        $this->addContractsColumnIfMissing('type_dob', "{$hijriGregorian} NOT NULL DEFAULT 'hijri'");
        $this->addContractsColumnIfMissing('type_dob_property_owner', "{$hijriGregorian} NOT NULL DEFAULT 'hijri'");
        $this->addContractsColumnIfMissing('type_dob_property_owner_agent', "{$hijriGregorian} NULL");
        $this->addContractsColumnIfMissing('type_tenant_dob', "{$hijriGregorian} NOT NULL DEFAULT 'hijri'");
        $this->addContractsColumnIfMissing('type_dob_tenant_agent', "{$hijriGregorian} NULL");
        $this->addContractsColumnIfMissing('type_contract_starting_date', "{$hijriGregorian} NOT NULL DEFAULT 'hijri'");
        $this->addContractsColumnIfMissing('type_instrument_history', "{$hijriGregorian} NULL");
        $this->addContractsColumnIfMissing('type_date_first_registration', "{$hijriGregorian} NULL");
        $this->addContractsColumnIfMissing('type_agency_instrument_date_of_property_owner', "{$hijriGregorian} NULL");

        $this->addContractsColumnIfMissing('split_ac', 'INT NULL');
        $this->addContractsColumnIfMissing('window_ac', 'INT NULL');
        $this->addContractsColumnIfMissing('kitchen_tank', $boolFalse);
        $this->addContractsColumnIfMissing('furnished', $boolFalse);
        $this->addContractsColumnIfMissing('type_furnished', $varchar100);
        $this->addContractsColumnIfMissing('electricity_meter', $boolFalse);
        $this->addContractsColumnIfMissing('water_meter', $boolFalse);
        $this->addContractsColumnIfMissing('expiry_date', 'DATE NULL');

        $this->addContractsColumnIfMissing('contract_status_id', 'BIGINT UNSIGNED NULL');
    }

    private function dropContractsColumnIfExists(string $column): void
    {
        if (! Schema::hasColumn('contracts', $column)) {
            return;
        }

        $this->withoutForeignKeyChecks(function () use ($column): void {
            DB::statement("ALTER TABLE `contracts` DROP COLUMN `{$column}`");
        });
    }

    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        $this->addNewContractsColumns();

        if (
            Schema::hasTable('tenant_roles')
            && Schema::hasColumn('contracts', 'tenant_role_id')
        ) {
            try {
                DB::statement('
                    ALTER TABLE `contracts`
                    ADD CONSTRAINT `contracts_tenant_role_id_foreign`
                    FOREIGN KEY (`tenant_role_id`) REFERENCES `tenant_roles` (`id`) ON DELETE CASCADE
                ');
            } catch (\Throwable) {
                // FK may already exist
            }
        }

        if (
            Schema::hasTable('contract_statuses')
            && Schema::hasColumn('contracts', 'contract_status_id')
        ) {
            try {
                DB::statement('
                    ALTER TABLE `contracts`
                    ADD CONSTRAINT `contracts_contract_status_id_foreign`
                    FOREIGN KEY (`contract_status_id`) REFERENCES `contract_statuses` (`id`) ON DELETE SET NULL
                ');
            } catch (\Throwable) {
                // FK may already exist
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

   
        $this->addContractsColumnIfMissing('property_owner_dob', 'TEXT NULL');

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

            $this->dropContractsColumnIfExists('property_owner_dob_hijri');
            $this->dropContractsColumnIfExists('property_owner_dob_gregorian');
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
            $this->dropContractsColumnIfExists($column);
        }
    }
};
