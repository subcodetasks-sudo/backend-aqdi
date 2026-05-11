<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Enums\ReceivedContractStatus;
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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Full contract for admin: scalar columns + relations with readable names (city, region, types, etc.).
 */
class AdminContractDetailResource extends JsonResource
{
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

        return array_merge($full, $enriched, [
            'relation_labels' => $this->relationLabels($c),
            'documentation_deadline_at' => $c->documentationDeadlineAt()?->format('Y-m-d H:i:s'),
        ]);
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
            'contract_payments' => $this->paymentsSummary($c->contractPayments),
            'tenant_role' => $this->tenantRoleSummary($c->tenantRole),
        ];
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

        return [
            'id' => $m->id,
            'period' => $m->period ?? null,
            'name' => $this->translatedName($m, 'note'),
            'note_ar' => $m->note_ar ?? null,
            'note_en' => $m->note_en ?? null,
            'note_trans' => $m->note_trans ?? null,
            'price' => $m->price ?? null,
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
            ])
        );
    }

    private function unitSummary(?UnitsReal $m): ?array
    {
        if ($m === null) {
            return null;
        }

        return [
            'id' => $m->id,
            'real_estates_units_id' => $m->real_estates_units_id ?? null,
            'unit_number' => $m->unit_number ?? null,
            'unit_area' => $m->unit_area ?? null,
            'floor_number' => $m->floor_number ?? null,
            'unit_type_id' => $m->unit_type_id ?? null,
            'electricity_meter_number' => $m->electricity_meter_number ?? null,
            'water_meter_number' => $m->water_meter_number ?? null,
        ];
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

    private function contractStatusSummary(?ContractStatus $m): ?array
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
            'order' => $m->order ?? null,
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
        ];
    }
}
