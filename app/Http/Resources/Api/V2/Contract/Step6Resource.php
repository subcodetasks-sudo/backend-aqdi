<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Api\V2\Contract\Concerns\MapsContractStatusFields;
use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use App\Support\DocFee;
use App\Support\HijriDobParts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step6Resource extends JsonResource
{
    use MapsContractStatusFields;
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        $docFee = DocFee::forContract($this->resource);
        $startingDate = HijriDobParts::split($this->contract_starting_date);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'contract_type' => $this->contract_type,

            'contract_starting_date' => $this->contract_starting_date,
            'contract_starting_date_day' => $startingDate['day'] ?? null,
            'contract_starting_date_month' => $startingDate['month'] ?? null,
            'contract_starting_date_year' => $startingDate['year'] ?? null,
            'type_contract_starting_date' => $this->type_contract_starting_date ?? 'hijri',

            // مدة جاهزة (contract_periods)
            'contract_term_in_years' => $this->contract_term_in_years,
            'price_contract_term' => $docFee['doc_fee']
                ?? optional($this->contractTermInYears)->price,

            // مدة أخرى — ضمن بيانات الخطوة السادسة
            'duration_preset' => $this->duration_preset,
            'duration_years' => $this->duration_years,
            'duration_months' => $this->duration_months,
            'total_months' => $this->total_months,

            'payment_type_id' => $this->payment_type_id,
            'annual_rent_amount_for_the_unit' => $this->annual_rent_amount_for_the_unit,
            'conditions' => (bool) ($this->other_conditions !== null && $this->other_conditions !== ''),
            'other_conditions' => $this->other_conditions,
            'additional_terms' => (bool) $this->additional_terms,
            'text_additional_terms' => $this->text_additional_terms,
            'daily_fine' => $this->daily_fine,
            'tenant_roles' => (bool) $this->tenant_roles,
            'tenant_role_id' => $this->tenant_role_id,
            'tenant_role_ids' => $this->tenant_role_ids ?? [],

            'doc_fee' => $docFee['doc_fee'] ?? null,
            'doc_fee_lines' => $docFee['doc_fee_lines'] ?? [],
            'billable_years' => $docFee['billable_years'] ?? null,
            'has_extra_months' => $docFee['has_extra_months'] ?? false,

            ...$this->contractStatusFields(),
            'step' => $this->step,
        ];
    }
}
