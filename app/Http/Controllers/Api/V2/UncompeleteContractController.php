<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\Contract\Step1Resource;
use App\Http\Resources\Api\V2\Contract\Step2Resource;
use App\Http\Resources\Api\V2\Contract\Step3Resource;
use App\Http\Resources\Api\V2\Contract\Step4Resource;
use App\Http\Resources\Api\V2\Contract\Step5Resource;
use App\Http\Resources\Api\V2\ContractResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Support\DocFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UncompeleteContractController extends Controller
{
    use Responser;

    public function checkUncompletedContract(): JsonResponse
    {
        $contract = Contract::query()
            ->where('user_id', auth()->id())
            ->where('is_completed', false)
            ->where('is_delete', false)
            ->latest('created_at')
            ->first();

        if ($contract) {
            $data = [
                'check' => true,
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
                'step' => $contract->step,
            ];
        } else {
            $data = [
                'check' => false,
            ];
        }

        return $this->apiResponse($data, trans('api.success'));
    }

    public function getUncompletedContractStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'string'],
        ]);

        $contract = Contract::query()
            ->where('user_id', auth()->id())
            ->where('uuid', $validated['uuid'])
            ->where('is_delete', false)
            ->first();

        if (! $contract) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        }

        if ($contract->is_completed) {
            return $this->errorMessage(trans('api.completed_contract'));
        }

        $contract->loadMissing(['realEstate', 'contractTermInYears', 'contractStatus']);

        $previousSteps = $this->buildPreviousStepsData($contract);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => array_merge([
                'step' => (int) $contract->step,
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ], $previousSteps),
        ], 200);
    }

    /**
     * @return list<int>
     */
    private function applicableStepNumbers(Contract $contract): array
    {
        if (! Contract::shouldSkipInitialSteps($contract->instrument_type)) {
            return [1, 2, 3, 4, 5, 6];
        }

        if ($contract->instrument_type === 'lease_renewal') {
            return [3, 5, 6];
        }

        return [3, 4, 5, 6];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreviousStepsData(Contract $contract): array
    {
        $currentStep = (int) $contract->step;
        $data = [];

        foreach ($this->applicableStepNumbers($contract) as $stepNumber) {
            if ($stepNumber >= $currentStep) {
                break;
            }

            $data['step'.$stepNumber] = $this->resolveStepPayload($stepNumber, $contract);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|Step1Resource|Step2Resource|Step3Resource|Step4Resource|Step5Resource
     */
    private function resolveStepPayload(int $stepNumber, Contract $contract): mixed
    {
        return match ($stepNumber) {
            1 => new Step1Resource($contract),
            2 => new Step2Resource($contract),
            3 => new Step3Resource($contract),
            4 => new Step4Resource($contract),
            5 => new Step5Resource($contract),
            6 => [
                'contract' => new ContractResource($contract),
                'price_contract_term' => DocFee::forContract($contract)['doc_fee']
                    ?? ($contract->contractTermInYears->price ?? null),
                'doc_fee' => DocFee::forContract($contract),
            ],
            default => [],
        };
    }
}
