<?php

namespace App\Modules\Catalog\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\BankAccount;
use App\Modules\Catalog\Models\City;
use App\Modules\Catalog\Models\ContractPeriod;
use App\Modules\Catalog\Models\Paperwork;
use App\Modules\Catalog\Models\PaymentType;
use App\Modules\Catalog\Models\ReaEstatType;
use App\Modules\Catalog\Models\ReaEstatUsage;
use App\Modules\Catalog\Models\Region;
use App\Modules\Catalog\Models\ServicesPricing;
use App\Modules\Catalog\Models\UnitType;
use App\Modules\Catalog\Models\UsageUnit;
use App\Modules\Catalog\Requests\Api\FilterByContractTypeRequest;
use App\Modules\Catalog\Requests\Api\ListCitiesRequest;
use App\Modules\Catalog\Resources\BankAccountResource;
use App\Modules\Catalog\Resources\CityResource;
use App\Modules\Catalog\Resources\ContractPeriodResource;
use App\Modules\Catalog\Resources\PaperworkResource;
use App\Modules\Catalog\Resources\PaymentTypeResource;
use App\Modules\Catalog\Resources\ReaEstatTypeResource;
use App\Modules\Catalog\Resources\ReaEstatUsageResource;
use App\Modules\Catalog\Resources\RegionResource;
use App\Modules\Catalog\Resources\ServicePricingResource;
use App\Modules\Catalog\Resources\UnitTypeResource;
use App\Modules\Catalog\Resources\UnitUsageResource;
use App\Shared\Responses\Responser;

class CatalogLookupController extends Controller
{
    use Responser;

    public function cities(ListCitiesRequest $request)
    {
        $cities = City::where('region_id', $request->region_id)->get();

        return $this->apiResponse(CityResource::collection($cities), trans('api.success'));
    }

    public function regions()
    {
        $regions = Region::orderBy('id', 'desc')->get();

        return $this->apiResponse(RegionResource::collection($regions), trans('api.success'));
    }

    public function bankAccounts()
    {
        $bankAccounts = BankAccount::get();

        return $this->apiResponse(BankAccountResource::collection($bankAccounts), trans('api.success'));
    }

    public function servicesPricing(FilterByContractTypeRequest $request)
    {
        $servicesPricing = ServicesPricing::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(ServicePricingResource::collection($servicesPricing), trans('api.success'));
    }

    public function paperwork(FilterByContractTypeRequest $request)
    {
        $paperwork = Paperwork::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(PaperworkResource::collection($paperwork), trans('api.success'));
    }

    public function realEstatType(FilterByContractTypeRequest $request)
    {
        $realEstatTypes = ReaEstatType::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(ReaEstatTypeResource::collection($realEstatTypes), trans('api.success'));
    }

    public function realEstatUsage(FilterByContractTypeRequest $request)
    {
        $realEstatUsage = ReaEstatUsage::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(ReaEstatUsageResource::collection($realEstatUsage), trans('api.success'));
    }

    public function unitsTypes(FilterByContractTypeRequest $request)
    {
        $unitsTypes = UnitType::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(UnitTypeResource::collection($unitsTypes), trans('api.success'));
    }

    public function unitsUsages(FilterByContractTypeRequest $request)
    {
        $unitsUsages = UsageUnit::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(UnitUsageResource::collection($unitsUsages), trans('api.success'));
    }

    public function paymentsTypes(FilterByContractTypeRequest $request)
    {
        $paymentsTypes = PaymentType::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(PaymentTypeResource::collection($paymentsTypes), trans('api.success'));
    }

    public function contractPeriods(FilterByContractTypeRequest $request)
    {
        $contractPeriods = ContractPeriod::where('contract_type', $request->contract_type)->get();

        return $this->apiResponse(ContractPeriodResource::collection($contractPeriods), trans('api.success'));
    }
}
