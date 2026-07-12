<?php

namespace App\Http\Requests\Admin;

use App\Models\Contract;
use App\Models\ContractPeriod;
use App\Services\Admin\RefundableContractService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreContractPaidByEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('employee_id');
        $this->request->remove('is_paid');
        $this->request->remove('contract_uuid');

        if ($this->has('draft_contract_number')) {
            $this->merge([
                'draft_contract_number' => trim((string) $this->input('draft_contract_number')),
            ]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_mobile' => ['required', 'string', 'max:32'],
            'contract_type' => ['required', 'in:housing,commercial'],
            'contract_period_id' => ['required', 'integer', 'exists:contract_periods,id'],
            'draft_contract_number' => ['required', 'string', 'max:32'],
            'draft_contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('contract_period_id') && $this->filled('contract_type')) {
                $periodType = ContractPeriod::query()
                    ->whereKey($this->integer('contract_period_id'))
                    ->value('contract_type');

                if ($periodType && $periodType !== $this->input('contract_type')) {
                    $validator->errors()->add(
                        'contract_period_id',
                        'مدة العقد لا تطابق نوع العقد المختار.'
                    );
                }
            }

            if (! $this->filled('draft_contract_number')) {
                return;
            }

            $draftId = app(RefundableContractService::class)
                ->resolveContractIdFromDraftNumber((string) $this->input('draft_contract_number'));

            if ($draftId === null) {
                $validator->errors()->add(
                    'draft_contract_number',
                    trans('api.invalid_draft_contract_number')
                );

                return;
            }

            $draft = Contract::query()
                ->notDeleted()
                ->whereKey($draftId)
                ->first();

            if (! $draft) {
                $validator->errors()->add(
                    'draft_contract_number',
                    trans('api.invalid_draft_contract_number')
                );

                return;
            }

            $this->merge(['draft_contract_id' => $draft->id]);
        });
    }
}
