<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\RealEstateControllor as ApiRealEstateControllor;
use App\Http\Requests\Api\V2\RealEstate\Step1RealEstateRequest;
use App\Http\Requests\Api\V2\RealEstate\UpdateStep1RealEstateRequest;
use App\Http\Requests\Api\V2\RealEstate\Step2RealEstateRequest;
use App\Http\Resources\Api\V2\RealEstate\RealEstateResource;
use App\Http\Resources\Api\V2\RealEstate\Step1RealEstateResource;
use App\Http\Resources\Api\V2\RealEstate\Step2RealEstateResource;
use App\Models\RealEstate;
use App\Support\DateInputNormalizer;
use App\Support\HijriDobParts;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

class RealEstateControllor extends ApiRealEstateControllor
{
    /**
     * @return array<int, string>
     */
    protected function realEstateEagerLoads(): array
    {
        return [
            'propertyType',
            'propertyUsages',
            'tenantEntityCity',
            'tenantEntityRegion',
        ];
    }

    protected function toStep1RealEstateRequest(Request $request): Step1RealEstateRequest
    {
        $form = Step1RealEstateRequest::createFrom($request);
        $form->setContainer(app())->setRedirector(app(Redirector::class));
        $form->validateResolved();

        return $form;
    }

    protected function toStep2RealEstateRequest(Request $request): Step2RealEstateRequest
    {
        $form = Step2RealEstateRequest::createFrom($request);
        $form->setContainer(app())->setRedirector(app(Redirector::class));
        $form->validateResolved();
        return $form;
    }

    public function index()
    {
        $user = auth()->user();
        $data = RealEstate::query()
            ->where('user_id', $user->id)
            ->with(['propertyType', 'propertyUsages'])
            ->get();
        return $this->apiResponse(RealEstateResource::collection($data), trans('api.real_estate'));
    }

    public function all()
    {
        $user = auth()->user();
        $realEstates = RealEstate::query()
            ->where('user_id', $user->id)
            ->with(['propertyType', 'propertyUsages'])
            ->get();

        if (! $realEstates) {
            return $this->errorMessage(trans('api.not_have_real'), 404);
        }

        return $this->apiResponse(RealEstateResource::collection($realEstates), trans('api.real_estate'));
    }

    public function show($id)
    {
        $user = auth()->user();
        $realEstate = RealEstate::query()
            ->where('user_id', $user->id)
            ->with(['propertyType', 'propertyUsages'])
            ->findOrFail($id);
        return $this->apiResponse(new RealEstateResource($realEstate), trans('api.real_estate'), 200);
    }

    public function step1(Request $request)
    {
        $request = $this->toStep1RealEstateRequest($request);
        $realEstate = RealEstate::create($request->attributesForCreate(Auth::id()));
        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step1RealEstateResource($realEstate->fresh($this->realEstateEagerLoads())),
        ]);
    }

    public function step2(Request $request)
    {
        return $this->saveOwnerStep($request, false);
    }

    /** @deprecated Alias of step2 — location is now part of step1. */
    public function step3(Request $request)
    {
        return $this->step2($request);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    protected function saveOwnerStep(Request $request, bool $isUpdate)
    {
        $form = $this->toStep2RealEstateRequest($request);

        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($form->integer('id'));

        $data = [
            'name_real_estate' => $form->input('name_real_estate'),
            'name_owner' => $form->name_owner,
            'property_owner_id_num' => $form->property_owner_id_num,
            'user_id' => $user->id,
            'type_dob_property_owner' => $form->input('type_dob_property_owner', 'hijri'),
            'property_owner_dob_hijri' => HijriDobParts::combine(
                $form->property_owner_dob_day,
                $form->property_owner_dob_month,
                $form->property_owner_dob_year
            ),
            'property_owner_mobile' => $form->property_owner_mobile,
            'property_owner_iban' => $form->property_owner_iban,
            'add_legal_agent_of_owner' => $form->add_legal_agent_of_owner,
            'step' => 2,
        ];

        $hasAgent = in_array((string) $form->add_legal_agent_of_owner, ['1', 'true'], true)
            || $form->add_legal_agent_of_owner === 1
            || $form->add_legal_agent_of_owner === true;

        if ($hasAgent) {
            $data['id_num_of_property_owner_agent'] = $form->id_num_of_property_owner_agent;
            $data['type_dob_property_owner_agent'] = $form->input('type_dob_property_owner_agent', 'hijri');
            $data['dob_of_property_owner_agent'] = HijriDobParts::combine(
                $form->input('dob_of_property_owner_agent_day'),
                $form->input('dob_of_property_owner_agent_month'),
                $form->input('dob_of_property_owner_agent_year')
            );
            $data['mobile_of_property_owner_agent'] = $form->mobile_of_property_owner_agent;
            $data['agency_number_in_instrument_of_property_owner'] = $form->agency_number_in_instrument_of_property_owner;
            $data['type_agency_instrument_date_of_property_owner'] = $form->input(
                'type_agency_instrument_date_of_property_owner',
                'hijri'
            );
            $data['agency_instrument_date_of_property_owner'] = DateInputNormalizer::combineFromParts(
                $form->input('agency_instrument_date_of_property_owner_day'),
                $form->input('agency_instrument_date_of_property_owner_month'),
                $form->input('agency_instrument_date_of_property_owner_year')
            );
            if ($request->hasFile('copy_of_the_authorization_or_agency')) {
                $data['copy_of_the_authorization_or_agency'] = $request->file('copy_of_the_authorization_or_agency')
                    ->store('authorizations', 'public');
            } elseif (! $isUpdate) {
                $data['copy_of_the_authorization_or_agency'] = $realEstate->copy_of_the_authorization_or_agency;
            }
        } else {
            $data['id_num_of_property_owner_agent'] = null;
            $data['type_dob_property_owner_agent'] = null;
            $data['dob_of_property_owner_agent'] = null;
            $data['mobile_of_property_owner_agent'] = null;
            $data['agency_number_in_instrument_of_property_owner'] = null;
            $data['agency_instrument_date_of_property_owner'] = null;
            $data['type_agency_instrument_date_of_property_owner'] = null;
            $data['copy_of_the_authorization_or_agency'] = null;
        }

        $realEstate->update($data);

        return response()->json([
            'message' => $isUpdate ? trans('api.updated_success') : trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step2RealEstateResource($realEstate->fresh($this->realEstateEagerLoads())),
        ]);
    }

    public function updateStep1(Request $request)
    {
        $form = UpdateStep1RealEstateRequest::createFrom($request);
        $form->setContainer(app())->setRedirector(app(Redirector::class));
        $form->validateResolved();

        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($form->input('id'));

        $data = array_merge([
            'name_real_estate' => $form->input('name_real_estate'),
            'contract_ownership' => $form->input('contract_ownership'),
            'contract_type' => $form->input('contract_type'),
            'instrument_number' => $form->input('instrument_number'),
            'instrument_history' => $form->input('instrument_history'),
            'real_estate_registry_number' => $form->input('real_estate_registry_number'),
            'date_first_registration' => $form->input('date_first_registration'),
            'property_owner_is_deceased' => $form->input('property_owner_is_deceased'),
            'number_of_units_in_realestate' => $form->input('number_of_units_in_realestate'),
            'instrument_type' => $form->input('instrument_type'),
            'property_type_id' => $form->input('property_type_id'),
            'property_usages_id' => $form->input('property_usages_id'),
            'number_of_floors' => $form->input('number_of_floors'),
            'age_of_the_property' => $form->input('age_of_the_property'),
            'number_of_units_per_floor' => $form->input('number_of_units_per_floor'),
            'step' => 1,
        ], $form->locationAttributesForPayload());

        if ($form->input('instrument_type') === RealEstate::INSTRUMENT_TYPE_OWNER_ENDOWMENT) {
            $data['is_multiple_trusteeship_deed_copy'] = $form->boolean('is_multiple_trusteeship_deed_copy');
        }

        if ($form->input('instrument_type') === 'electronic' && $form->filled('instrument_history')) {
            $data['instrument_history'] = date('Y-m-d', strtotime((string) $form->input('instrument_history')));
            $data['type_instrument_history'] = $form->input('type_instrument_history', 'hijri');
        }

        if ($form->input('instrument_type') === 'strong_argument' && $form->filled('date_first_registration')) {
            $data['type_date_first_registration'] = $form->input('type_date_first_registration', 'hijri');
        }

        if ($request->hasFile('image_instrument')) {
            $data['image_instrument'] = $request->file('image_instrument')->store('images/real_estates', 'public');
        }
        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/real_estates', 'public');
        }

        if ($request->hasFile('copy_of_the_endowment_registration_certificate')) {
            $data['copy_of_the_endowment_registration_certificate'] = $request->file('copy_of_the_endowment_registration_certificate')
                ->store('real_estates/endowment-registration-certificates', 'public');
        }
        if ($request->hasFile('copy_of_the_trusteeship_deed')) {
            $data['copy_of_the_trusteeship_deed'] = $request->file('copy_of_the_trusteeship_deed')
                ->store('real_estates/trusteeship-deeds', 'public');
        }
        if ($request->hasFile('copy_of_guardians_power_of_attorney_for_agent')) {
            $data['copy_of_guardians_power_of_attorney_for_agent'] = $request->file('copy_of_guardians_power_of_attorney_for_agent')
                ->store('real_estates/guardians-power-of-attorney', 'public');
        }

        $realEstate->update($data);

        return response()->json([
            'message' => trans('api.updated_success'),
            'code' => 200,
            'success' => true,
            'data' => new Step1RealEstateResource($realEstate->fresh($this->realEstateEagerLoads())),
        ]);
    }

    public function updateStep2(Request $request)
    {
        return $this->saveOwnerStep($request, true);
    }

    /** @deprecated Alias of updateStep2 — location is now part of updateStep1. */
    public function updateStep3(Request $request)
    {
        return $this->updateStep2($request);
    }

    public function delete($id)
    {
        try {
            $user = Auth::user();
            $realEstate = RealEstate::with('units')
                ->where('user_id', $user->id)
                ->findOrFail($id);

            if ($realEstate->units->isNotEmpty()) {
                foreach ($realEstate->units as $unit) {
                    $unit->delete();
                }
            }

            $realEstate->delete();

            return $this->successMessage(trans('api.success'), 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }
}
