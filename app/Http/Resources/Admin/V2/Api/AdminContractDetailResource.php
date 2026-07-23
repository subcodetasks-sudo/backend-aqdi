<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Enums\ReceivedContractStatus;
use App\Http\Resources\Admin\V2\Api\Concerns\ResolvesContractPaymentForAdmin;
use App\Http\Resources\Admin\V2\Api\Concerns\ResolvesContractReturnAcceptance;
use App\Http\Resources\Admin\V2\Api\Concerns\ResolvesContractReturnOrderFields;
use App\Http\Resources\Api\V2\UnitResource;
use App\Models\Account;
use App\Models\City;
use App\Models\ContractPeriod;
use App\Models\ContractStatus;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\RealEstate;
use App\Models\ReceivedContract;
use App\Models\ReaEstatType;
use App\Models\ReaEstatUsage;
use App\Models\Region;
use App\Models\TenantRole;
use App\Models\UnitType;
use App\Models\UnitsReal;
use App\Models\UsageUnit;
use App\Models\User;
use App\Support\HijriDobParts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Full contract for admin: scalar columns + relations with readable names (city, region, types, etc.).
 */
class AdminContractDetailResource extends JsonResource
{
    use ResolvesContractPaymentForAdmin;
    use ResolvesContractReturnAcceptance;
    use ResolvesContractReturnOrderFields;
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $c = $this->resource;
        $full = $c->toArray();

        foreach (array_keys($c->getRelations()) as $relationName) {
            unset($full[Str::snake($relationName)]);
        }

        // Normalize file columns (paths from `store(..., 'public')`, e.g. images/contracts/….png).
        $attrs = $c->getAttributes();
        foreach ($this->contractStoragePathColumns() as $column) {
            $raw = array_key_exists($column, $attrs)
                ? $attrs[$column]
                : ($full[$column] ?? null);
            if ($raw === null && ! array_key_exists($column, $full)) {
                continue;
            }
            $full[$column] = $this->publicStorageUrl($raw);
        }

        $enriched = $this->enrichedRelations($c);

        return array_merge($full, $enriched, $this->step4TenantFields($c), $this->ownerAndDateSplitFields($c, $full), $this->returnOrderFields(), $this->returnAcceptanceFields(), $this->contractPaymentFields(), [
            'relation_labels' => $this->relationLabels($c),
            'documentation_deadline_at' => $c->documentationDeadlineAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Day/month/year splits for owner, owner agent and contract start date
     * (mirrors the parts the frontend sends in steps 3 and 6), plus aliases
     * for legacy/alternate column names used by the create-contract steps.
     *
     * @param  array<string, mixed>  $full
     * @return array<string, mixed>
     */
    private function ownerAndDateSplitFields($c, array $full): array
    {
        $ownerDob = $this->splitDateParts($c->property_owner_dob);
        $ownerAgentDob = $this->splitDateParts($c->dob_of_property_owner_agent);
        $startDate = $this->splitDateParts($c->contract_starting_date);

        return [
            'property_owner_dob_day' => $ownerDob['day'],
            'property_owner_dob_month' => $ownerDob['month'],
            'property_owner_dob_year' => $ownerDob['year'],
            'dob_of_property_owner_agent_day' => $ownerAgentDob['day'],
            'dob_of_property_owner_agent_month' => $ownerAgentDob['month'],
            'dob_of_property_owner_agent_year' => $ownerAgentDob['year'],
            'contract_starting_date_day' => $startDate['day'],
            'contract_starting_date_month' => $startDate['month'],
            'contract_starting_date_year' => $startDate['year'],
            // Frontend step-5 aliases (canonical columns: tootal_rooms / The_number_of_the_toilet).
            'number_of_rooms' => $full['number_of_rooms'] ?? $c->tootal_rooms,
            'The_number_of_toilets' => $full['The_number_of_toilets'] ?? $c->The_number_of_the_toilet,
            // Step-6 sends `conditions`; other_conditions_list is canonical (multi).
            'conditions' => $full['conditions']
                ?? (
                    (is_array($c->other_conditions_list) && $c->other_conditions_list !== [])
                    || ($c->other_conditions !== null && trim((string) $c->other_conditions) !== '')
                ),
            'other_conditions_list' => $this->resolvedOtherConditionsList($c),
            'other_conditions_count' => count($this->resolvedOtherConditionsList($c)),
        ];
    }

    /**
     * @return list<string>
     */
    private function resolvedOtherConditionsList($c): array
    {
        $list = $c->other_conditions_list;
        if (is_array($list) && $list !== []) {
            return array_values(array_filter(array_map(
                static fn ($v) => is_scalar($v) ? trim((string) $v) : '',
                $list
            )));
        }

        if ($c->other_conditions !== null && trim((string) $c->other_conditions) !== '') {
            return [trim((string) $c->other_conditions)];
        }

        return [];
    }

    /**
     * Split DD-MM-YYYY (hijri parts) or YYYY-MM-DD (gregorian) into day/month/year.
     *
     * @return array{day: ?string, month: ?string, year: ?string}
     */
    private function splitDateParts(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return ['day' => null, 'month' => null, 'year' => null];
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim($value), $m)) {
            return [
                'day' => str_pad($m[3], 2, '0', STR_PAD_LEFT),
                'month' => str_pad($m[2], 2, '0', STR_PAD_LEFT),
                'year' => $m[1],
            ];
        }

        return HijriDobParts::split($value);
    }

    /**
     * Same tenant / Step4 payload shape as API V2 Step4Resource.
     *
     * @return array<string, mixed>
     */
    private function step4TenantFields($c): array
    {
        $tenantDob = HijriDobParts::split($c->tenant_dob);
        $tenantAgentDob = HijriDobParts::split($c->dob_of_property_tenant_agent);

        return [
            'tenant_entity' => $c->tenant_entity,
            'tenant_id_num' => $c->tenant_id_num,
            'tenant_dob' => $c->tenant_dob,
            'tenant_dob_day' => $tenantDob['day'],
            'tenant_dob_month' => $tenantDob['month'],
            'tenant_dob_year' => $tenantDob['year'],
            'type_tenant_dob' => $c->type_tenant_dob ?? 'hijri',
            'type_dob_tenant_agent' => $c->type_dob_tenant_agent ?? 'hijri',
            'tenant_mobile' => $c->tenant_mobile,
            'region_of_the_tenant_legal_agent' => $c->region_of_the_tenant_legal_agent,
            'city_of_the_tenant_legal_agent' => $c->city_of_the_tenant_legal_agent,
            'tenant_entity_unified_registry_number' => $c->tenant_entity_unified_registry_number,
            'authorization_type' => $c->authorization_type,
            'copy_of_the_owner_record' => $this->publicStorageUrl(
                $c->getAttributes()['copy_of_the_owner_record'] ?? $c->copy_of_the_owner_record
            ),
            'id_num_of_property_tenant_agent' => $c->id_num_of_property_tenant_agent,
            'mobile_of_property_tenant_agent' => $c->mobile_of_property_tenant_agent,
            'dob_of_property_tenant_agent' => $c->dob_of_property_tenant_agent,
            'dob_of_property_tenant_agent_day' => $tenantAgentDob['day'],
            'dob_of_property_tenant_agent_month' => $tenantAgentDob['month'],
            'dob_of_property_tenant_agent_year' => $tenantAgentDob['year'],
            // Aliases matching the exact keys the frontend sends in step 4.
            'dobof_property_tenant_agent_day' => $tenantAgentDob['day'],
            'dobof_property_tenant_agent_month' => $tenantAgentDob['month'],
            'dobof_property_tenant_agent_year' => $tenantAgentDob['year'],
        ];
    }

    /**
     * Contract columns that store paths under public disk (same convention as API V2 Step resources).
     *
     * @return list<string>
     */
    private function contractStoragePathColumns(): array
    {
        return [
            'image_instrument',
            'image_instrument_from_the_back',
            'image_instrument_from_the_front',
            'image_address',
            'copy_of_the_authorization_or_agency',
            'copy_of_the_owner_record',
            'copy_of_the_endowment_registration_certificate',
            'copy_of_the_trusteeship_deed',
            'draft_before_paid',
            'draft_after_paid',
            'file',
            'strong_argument_photo',
            'photo_of_the_electronic',
            'Image_from_the_agency',
            'copy_power_of_attorney_from_heirs_to_agent',
            'Image_inheritance_certificate',
            'copy_of_guardians_power_of_attorney_for_agent',
        ];
    }

    /**
     * Full URL for paths stored via `store(..., 'public')` (relative to the public disk root).
     */
    private function publicStorageUrl(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (! is_string($raw)) {
            if (! is_scalar($raw)) {
                return null;
            }
            $raw = (string) $raw;
        }

        $path = trim($raw);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (Str::startsWith($path, '/storage/')) {
            return url($path);
        }

        $path = ltrim($path, '/');
        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return asset('storage/' . $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichedRelations($c): array
    {
        return [
            'user' => $this->userSummary($c->user),
            'real_estate' => $this->realEstateSummary($c->realEstate),
            'unit' => $this->unitSummary($c->unit),
            'units' => $c->relationLoaded('units')
                ? $c->units->map(fn ($u) => $this->unitSummary($u))->filter()->values()->all()
                : [],
            'units_count' => $c->relationLoaded('units') ? $c->units->count() : 0,
            'property_type' => $this->reaEstatTypeSummary($c->propertyType),
            'property_usages' => $this->reaEstatUsageSummary($c->propertyUsages),
            'property_region' => $this->regionSummary($c->propertyRegion),
            'property_city' => $this->citySummary($c->propertyCity),
            // Model links City (column region_of_the_tenant_legal_agent); name kept for API compatibility.
            'tenant_entity_legal_region' => $this->citySummary($c->tenantEntityLegalRegion),
            'tenant_entity_legal_city' => $this->citySummary($c->tenantEntityLegalCity),
            'tenant_entity_city' => $this->citySummary($c->tenantEntityCity),
            'tenant_entity_region' => $this->regionSummary($c->tenantEntityRegion),
            'unit_type' => $this->unitTypeSummary($c->unitType),
            'unit_usage' => $this->usageUnitSummary($c->unitUsage),
            'contract_term_in_years' => $this->contractPeriodSummary($c->contractTermInYears),
            'payment_type' => $this->paymentTypeSummary($c->paymentType),
            'account' => $this->accountSummary($c->account),
            'received_contract' => $this->receivedContractSummary($c->receivedContract),
            'contract_status' => $this->contractStatusSummary($c->contractStatus),
            'draft_contract_status' => $this->contractStatusSummary($c->draftContractStatus),
            'draft_contract_status_id' => $c->draft_contract_status_id,
            'contract_payments' => $this->paymentsSummary($c->contractPayments),
            'tenant_role' => $this->tenantRoleSummary($c->tenantRole),
            'tenant_roles_details' => $this->tenantRolesDetails($c),
            'accept_retrun_contract_employee' => $this->employeeSummary($c->acceptRetrunContractEmployee),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tenantRolesDetails($c): array
    {
        $ids = $c->tenant_role_ids ?? [];
        if (! is_array($ids) || $ids === []) {
            return [];
        }

        $values = is_array($c->tenant_role_values) ? $c->tenant_role_values : [];
        $roles = TenantRole::query()->whereIn('id', $ids)->orderBy('id')->get();

        return $roles->map(function (TenantRole $role) use ($values) {
            $key = (string) $role->id;
            $summary = $this->tenantRoleSummary($role);

            return array_merge($summary ?? [], [
                'value' => $values[$key] ?? $values[$role->id] ?? null,
            ]);
        })->values()->all();
    }

    /**
     * Flat human-readable labels for FKs (quick display in admin UI).
     *
     * @return array<string, string|null>
     */
    private function relationLabels($c): array
    {
        return [
            'user_name' => $this->translatedName($c->user, 'name'),
            'property_city' => $this->translatedName($c->propertyCity),
            'property_region' => $this->translatedName($c->propertyRegion),
            'property_type' => $this->translatedName($c->propertyType, 'name'),
            'property_usages' => $this->translatedName($c->propertyUsages, 'name'),
            'tenant_entity_city' => $this->translatedName($c->tenantEntityCity),
            'tenant_entity_region' => $this->translatedName($c->tenantEntityRegion),
            'tenant_entity_legal_city' => $this->translatedName($c->tenantEntityLegalCity),
            'tenant_entity_legal_region' => $this->translatedName($c->tenantEntityLegalRegion),
            'unit_type' => $this->translatedName($c->unitType, 'name'),
            'unit_usage' => $this->translatedName($c->unitUsage, 'name'),
            'contract_term' => $this->translatedName($c->contractTermInYears, 'note'),
            'payment_type' => $this->translatedName($c->paymentType, 'name'),
            'contract_status' => $c->contractStatus?->name,
            'draft_contract_status' => $c->draftContractStatus?->name,
            'tenant_role' => $c->tenantRole?->text_of_reason,
        ];
    }

    private function translatedName(?object $model, string $prefix = 'name'): ?string
    {
        if ($model === null) {
            return null;
        }
        $transKey = "{$prefix}_trans";
        if (isset($model->{$transKey}) && $model->{$transKey} !== null && $model->{$transKey} !== '') {
            return (string) $model->{$transKey};
        }
        foreach (["{$prefix}_ar", "{$prefix}_en", $prefix] as $attr) {
            if (isset($model->{$attr}) && $model->{$attr} !== null && $model->{$attr} !== '') {
                return (string) $model->{$attr};
            }
        }

        return null;
    }

    private function citySummary(?City $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $this->translatedName($m),
            'name_ar' => $m->name_ar ?? null,
            'name_en' => $m->name_en ?? null,
            'name_trans' => $m->name_trans ?? null,
            'region_id' => $m->region_id ?? null,
        ];
    }

    private function regionSummary(?Region $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $this->translatedName($m),
            'name_ar' => $m->name_ar ?? null,
            'name_en' => $m->name_en ?? null,
            'name_trans' => $m->name_trans ?? null,
        ];
    }

    private function reaEstatTypeSummary(?ReaEstatType $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $this->translatedName($m, 'name'),
            'name_ar' => $m->name_ar ?? null,
            'name_en' => $m->name_en ?? null,
            'name_trans' => $m->name_trans ?? null,
            'contract_type' => $m->contract_type ?? null,
        ];
    }

    private function reaEstatUsageSummary(?ReaEstatUsage $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $this->translatedName($m, 'name'),
            'name_ar' => $m->name_ar ?? null,
            'name_en' => $m->name_en ?? null,
            'name_trans' => $m->name_trans ?? null,
            'contract_type' => $m->contract_type ?? null,
        ];
    }

    private function unitTypeSummary(?UnitType $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $this->translatedName($m, 'name'),
            'name_ar' => $m->name_ar ?? null,
            'name_en' => $m->name_en ?? null,
            'name_trans' => $m->name_trans ?? null,
        ];
    }

    private function usageUnitSummary(?UsageUnit $m): ?array
    {
        if ($m === null) {
            return null;
        }

        $attrs = $m->getAttributes();

        return array_merge($attrs, [
            'id' => $m->id,
            'name' => $this->translatedName($m, 'name')
                ?? ($attrs['name_ar'] ?? $attrs['name_en'] ?? $attrs['name'] ?? null),
        ]);
    }

    private function contractPeriodSummary(?ContractPeriod $m): ?array
    {
        if ($m === null) {
            return null;
        }

        $payment = $this->contractPaymentFields();
        $price = is_numeric($payment['amount_payment'])
            ? (float) $payment['amount_payment']
            : ($m->price ?? null);

        return [
            'id' => $m->id,
            'period' => $m->period ?? null,
            'name' => $this->translatedName($m, 'note'),
            'note_ar' => $m->note_ar ?? null,
            'note_en' => $m->note_en ?? null,
            'note_trans' => $m->note_trans ?? null,
            'price' => $price,
            'contract_type' => $m->contract_type ?? null,
        ];
    }

    private function paymentTypeSummary(?PaymentType $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $this->translatedName($m, 'name'),
            'name_ar' => $m->name_ar ?? null,
            'name_en' => $m->name_en ?? null,
            'name_trans' => $m->name_trans ?? null,
            'contract_type' => $m->contract_type ?? null,
            'notes' => $m->notes ?? null,
        ];
    }

    private function userSummary(?User $u): ?array
    {
        if ($u === null) {
            return null;
        }

        return [
            'id' => $u->id,
            'name' => $u->name ?? null,
            'email' => $u->email ?? null,
            'mobile' => $u->mobile ?? null,
        ];
    }

    private function realEstateSummary(?RealEstate $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return array_merge(
            ['id' => $m->id],
            $m->only([
                'name_real_estate',
                'name_owner',
                'contract_type',
                'instrument_type',
                'street',
                'neighborhood',
                'property_city_id',
                'property_place_id',
                'building_number',
                'postal_code',
                'real_estate_registry_number',
                'address_url',
                'electricity_meter_ownership',
                'water_meter_ownership',
            ]),
            [
                'copy_of_guardians_power_of_attorney_for_agent' => $this->publicStorageUrl(
                    $m->getAttributes()['copy_of_guardians_power_of_attorney_for_agent'] ?? null
                ),
            ]
        );
    }

    /**
     * Full unit payload for admin (same fields as API V2 UnitResource).
     *
     * @return array<string, mixed>|null
     */
    private function unitSummary(?UnitsReal $m): ?array
    {
        if ($m === null) {
            return null;
        }

        if (! $m->relationLoaded('unitType')) {
            $m->loadMissing(['unitType', 'unitUsage', 'realEstate']);
        }

        return (new UnitResource($m))->resolve();
    }

    private function accountSummary(?Account $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'value_contract' => $m->valueContract ?? null,
        ];
    }

    private function receivedContractSummary(?ReceivedContract $r): ?array
    {
        if ($r === null) {
            return null;
        }

        return [
            'id' => $r->id,
            'contract_id' => $r->contract_id,
            'employee_id' => $r->employee_id,
            'status' => $r->status instanceof ReceivedContractStatus
                ? $r->status->value
                : $r->status,
            'notes' => $r->notes,
            'date_of_received' => $r->date_of_received,
            'employee' => $this->employeeSummary($r->employee),
        ];
    }

    private function employeeSummary(?Employee $e): ?array
    {
        if ($e === null) {
            return null;
        }

        return [
            'id' => $e->id,
            'name' => $e->name,
            'email' => $e->email,
            'phone' => $e->phone,
        ];
    }

    private function contractStatusSummary($m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $m->name,
            'color' => $m->color ?? null,
            'color_text' => $m->color_text ?? null,
            'description' => $m->description ?? null,
            'is_active' => (bool) ($m->is_active ?? false),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Payment>|null  $collection
     * @return list<array<string, mixed>>|null
     */
    private function paymentsSummary($collection): ?array
    {
        if ($collection === null || $collection->isEmpty()) {
            return $collection === null ? null : [];
        }

        return $collection->map(static function (Payment $p) {
            return [
                'id' => $p->id,
                'amount' => $p->amount ?? null,
                'status' => $p->status ?? null,
                'payment_date' => $p->payment_date ?? null,
                'payment_method' => $p->payment_method ?? null,
                'contract_uuid' => $p->contract_uuid ?? null,
                'name' => $p->name ?? null,
            ];
        })->values()->all();
    }

    private function tenantRoleSummary(?TenantRole $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'name' => $m->text_of_reason,
            'text_of_reason' => $m->text_of_reason,
            'service_definition' => $m->service_definition,
            'input_field_label' => $m->input_field_label,
            'input_field_type' => $m->input_field_type,
            'has_user_input' => $m->requiresUserInput(),
            'icon' => $m->icon,
            'input_icon' => $m->input_icon,
            'pop' => (bool) $m->pop,
        ];
    }
}
