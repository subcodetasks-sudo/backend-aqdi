<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\ContractPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractPeriodController extends Controller
{
    use Responser;

    /**
     * Get all contract periods
     */
    public function index(Request $request)
    {
        $query = ContractPeriod::query();

        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        $contracts = $query->latest()->paginate($request->get('per_page', 20));

        return $this->apiResponse($contracts, trans('api.success'));
    }

    /**
     * Get single contract period
     */
    public function show($id)
    {
        $contractPeriod = ContractPeriod::find($id);

        if (!$contractPeriod) {
            return $this->apiResponse(null, trans('api.contract_period_not_found'), false, 404);
        }

        return $this->apiResponse($contractPeriod, trans('api.success'));
    }

    /**
     * Create new contract period
     */
    public function create(Request $request)
    {
        $validator = $this->validateContractPeriod($request);
        
        if ($validator->fails()) {
            return $this->apiResponse(null, $validator->errors()->first(), false, 422);
        }

        $data = $this->prepareContractPeriodData($request);
        $contractPeriod = ContractPeriod::create($data);

        return $this->apiResponse($contractPeriod, trans('api.contract_period_created_successfully'), true, 201);
    }

    /**
     * Store new contract period (alias for create)
     */
    public function store(Request $request)
    {
        return $this->create($request);
    }

    /**
     * Update contract period
     */
    public function update(Request $request, $id)
    {
        $contractPeriod = ContractPeriod::find($id);

        if (!$contractPeriod) {
            return $this->apiResponse(null, trans('api.contract_period_not_found'), false, 404);
        }

        $validator = $this->validateContractPeriod($request, $id);

        if ($validator->fails()) {
            return $this->apiResponse(null, $validator->errors()->first(), false, 422);
        }

        $data = $this->prepareContractPeriodData($request);
        $contractPeriod->update($data);

        return $this->apiResponse($contractPeriod, trans('api.contract_period_updated_successfully'));
    }

    /**
     * Delete contract period
     */
    public function destroy($id)
    {
        $contractPeriod = ContractPeriod::find($id);

        if (!$contractPeriod) {
            return $this->apiResponse(null, trans('api.contract_period_not_found'), false, 404);
        }

        $contractPeriod->delete();

        return $this->apiResponse(null, trans('api.contract_period_deleted_successfully'));
    }

    /**
     * Validate contract period data
     */
    private function validateContractPeriod(Request $request, $id = null)
    {
        $rules = [
            'period' => 'required|string',
            'note_ar' => 'required|string',
            'contract_type' => 'required|in:housing,commercial',
        ];

        $rules['price'] = 'nullable|numeric|min:0';

        return Validator::make($request->all(), $rules);
    }

    /**
     * Prepare contract period data
     */
    private function prepareContractPeriodData(Request $request): array
    {
        return [
            'period' => $request->period,
            'note_ar' => $request->note_ar,
            'note_en' => $request->note_en,
            'contract_type' => $request->contract_type,
            'price' => $request->price ?? null,
        ];
    }
}
