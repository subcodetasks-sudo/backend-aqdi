<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\RealEstateControllor as ApiRealEstateControllor;
use App\Http\Requests\Api\V2\RealEstate\Step1RealEstateRequest;
use App\Http\Requests\Api\V2\RealEstate\Step2RealEstateRequest;
use App\Http\Requests\Api\V2\RealEstate\Step3RealEstateRequest;
use App\Http\Resources\Api\V2\RealEstate\RealEstateResource;
use App\Http\Resources\Api\V2\RealEstate\Step1RealEstateResource;
use App\Http\Resources\Api\V2\RealEstate\Step2RealEstateResource;
use App\Http\Resources\Api\V2\RealEstate\Step3RealEstateResource;
use App\Models\City;
use App\Models\RealEstate;
use App\Support\DateInputNormalizer;
use App\Support\HijriDobParts;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RealEstateControllor extends ApiRealEstateControllor
{
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

    protected function toStep3RealEstateRequest(Request $request): Step3RealEstateRequest
    {
        $form = Step3RealEstateRequest::createFrom($request);
        $form->setContainer(app())->setRedirector(app(Redirector::class));
        $form->validateResolved();
        return $form;
    }

    public function index()
    {
        $user = auth()->user();
        $data = RealEstate::where('user_id', $user->id)->get();
        return $this->apiResponse(RealEstateResource::collection($data), trans('api.real_estate'));
    }

    public function all()
    {
        $user = auth()->user();
        $realEstates = RealEstate::where('user_id', $user->id)->get();

        if (! $realEstates) {
            return $this->errorMessage(trans('api.not_have_real'), 404);
        }

        return $this->apiResponse(RealEstateResource::collection($realEstates), trans('api.real_estate'));
    }

    public function show($id)
    {
        $user = auth()->user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($id);
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
            'data' => new Step1RealEstateResource($realEstate->fresh()),
        ]);
    }

    public function step2(Request $request)
    {
        $request = $this->toStep2RealEstateRequest($request);
        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($request->id);
        $city = City::where('id', $request->property_city_id)
            ->where('region_id', $request->property_place_id)
            ->first();

        if (! $city) {
            return $this->errorMessage(trans('api.city_not_include_region'));
        }

        $data = [
            'property_place_id' => $request->property_place_id,
            'property_city_id' => $request->property_city_id,
            'neighborhood' => $request->neighborhood,
            'street' => $request->street,
            'building_number' => $request->building_number,
            'postal_code' => $request->postal_code,
            'extra_figure' => $request->extra_figure,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'user_id' => $user->id,
            'step' => 3,
        ];

        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/real_estates', 'public');
        }

        $realEstate->update($data);
        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step2RealEstateResource($realEstate->fresh()),
        ]);
    }

    public function step3(Request $request)
    {
        $request = $this->toStep3RealEstateRequest($request);
        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($request->id);
        $data = [
            'name_real_estate' => $request->input('name_real_estate'),
            'name_owner' => $request->name_owner,
            'user_id' => $user->id,
            'type_dob_property_owner' => $request->input('type_dob_property_owner', 'hijri'),
            //'property_owner_id_num' => $request->property_owner_id_num,
            'property_owner_dob_hijri' => HijriDobParts::combine(
                $request->property_owner_dob_day,
                $request->property_owner_dob_month,
                $request->property_owner_dob_year
            ),
            'property_owner_mobile' => $request->property_owner_mobile,
            'property_owner_iban' => $request->property_owner_iban,
            'add_legal_agent_of_owner' => $request->add_legal_agent_of_owner,
            'step' => 4,
        ];

        $hasAgent = in_array((string) $request->add_legal_agent_of_owner, ['1', 'true'], true)
            || $request->add_legal_agent_of_owner === 1
            || $request->add_legal_agent_of_owner === true;

        if ($hasAgent) {
            $data['id_num_of_property_owner_agent'] = $request->id_num_of_property_owner_agent;
            $data['type_dob_property_owner_agent'] = $request->input('type_dob_property_owner_agent', 'hijri');
            $data['dob_of_property_owner_agent'] = HijriDobParts::combine(
                $request->input('dob_of_property_owner_agent_day'),
                $request->input('dob_of_property_owner_agent_month'),
                $request->input('dob_of_property_owner_agent_year')
            );
            $data['mobile_of_property_owner_agent'] = $request->mobile_of_property_owner_agent;
            $data['agency_number_in_instrument_of_property_owner'] = $request->agency_number_in_instrument_of_property_owner;
            $data['type_agency_instrument_date_of_property_owner'] = $request->input(
                'type_agency_instrument_date_of_property_owner',
                'hijri'
            );
            $data['agency_instrument_date_of_property_owner'] = DateInputNormalizer::combineFromParts(
                $request->input('agency_instrument_date_of_property_owner_day'),
                $request->input('agency_instrument_date_of_property_owner_month'),
                $request->input('agency_instrument_date_of_property_owner_year')
            );
            if ($request->hasFile('copy_of_the_authorization_or_agency')) {
                $data['copy_of_the_authorization_or_agency'] = $request->file('copy_of_the_authorization_or_agency')
                    ->store('authorizations', 'public');
            } else {
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
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => new Step3RealEstateResource($realEstate->fresh()),
        ]);
    }

    public function updateStep1(Request $request)
    {
        $instrumentTypes = RealEstate::instrumentTypes();
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:real_estates,id',
            'name_real_estate' => 'nullable|string|max:255',
            'contract_ownership' => 'required|in:owner,tenant',
            'contract_type' => 'required|in:housing,commercial',
            'instrument_number' => [Rule::requiredIf($request->input('instrument_type') === 'electronic')],
            'instrument_history' => [Rule::requiredIf($request->input('instrument_type') === 'electronic')],
            'real_estate_registry_number' => [Rule::requiredIf($request->input('instrument_type') === 'strong_argument')],
            'date_first_registration' => [Rule::requiredIf($request->input('instrument_type') === 'strong_argument')],
            'property_type_id' => 'required|exists:rea_estat_types,id',
            'property_owner_is_deceased' => 'required|boolean',
            'number_of_floors' => 'required',
            'instrument_type' => ['nullable', Rule::in($instrumentTypes), 'required_if:property_owner_is_deceased,1'],
            'property_usages_id' => 'required_if:instrument_type,electronic,strong_argument',
            'number_of_units_in_realestate' => 'required_if:instrument_type,electronic,strong_argument|nullable|integer',
            'image_instrument' => 'nullable|image',
            'image_address' => 'nullable|image',
            'age_of_the_property' => 'nullable|integer|min:0',
            'number_of_units_per_floor' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'type_instrument_history' => 'nullable|in:hijri,gregorian',
            'type_date_first_registration' => 'nullable|in:hijri,gregorian',
        ], [
            'id.required' => 'معرف العقار مطلوب.',
            'id.exists' => 'العقار المحدد غير موجود.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first() ?: 'البيانات المدخلة غير صحيحة.',
                'code' => 422,
                'success' => false,
            ], 422);
        }

        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($request->id);

        $data = [
            'name_real_estate' => $request->input('name_real_estate'),
            'contract_ownership' => $request->contract_ownership,
            'contract_type' => $request->contract_type,
            'instrument_number' => $request->instrument_number,
            'instrument_history' => $request->instrument_history,
            'real_estate_registry_number' => $request->real_estate_registry_number,
            'date_first_registration' => $request->date_first_registration,
            'property_owner_is_deceased' => $request->property_owner_is_deceased,
            'number_of_units_in_realestate' => $request->number_of_units_in_realestate,
            'instrument_type' => $request->instrument_type,
            'property_type_id' => $request->property_type_id,
            'property_usages_id' => $request->property_usages_id,
            'number_of_floors' => $request->number_of_floors,
            'age_of_the_property' => $request->input('age_of_the_property'),
            'number_of_units_per_floor' => $request->input('number_of_units_per_floor'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'step' => 2,
        ];

        if ($request->input('instrument_type') === 'electronic' && $request->filled('instrument_history')) {
            $data['instrument_history'] = date('Y-m-d', strtotime((string) $request->instrument_history));
            $data['type_instrument_history'] = $request->input('type_instrument_history', 'hijri');
        }

        if ($request->input('instrument_type') === 'strong_argument' && $request->filled('date_first_registration')) {
            $data['type_date_first_registration'] = $request->input('type_date_first_registration', 'hijri');
        }

        if ($request->hasFile('image_instrument')) {
            $data['image_instrument'] = $request->file('image_instrument')->store('images/real_estates', 'public');
        }
        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/real_estates', 'public');
        }

        $realEstate->update($data);

        return response()->json([
            'message' => trans('api.updated_success'),
            'code' => 200,
            'success' => true,
            'data' => new Step1RealEstateResource($realEstate->fresh()),
        ]);
    }

    public function updateStep2(Request $request)
    {
        $request = $this->toStep2RealEstateRequest($request);
        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($request->id);

        $city = City::where('id', $request->property_city_id)
            ->where('region_id', $request->property_place_id)
            ->first();

        if (! $city) {
            return $this->errorMessage(trans('api.city_not_include_region'));
        }

        $data = [
            'property_place_id' => $request->property_place_id,
            'property_city_id' => $request->property_city_id,
            'neighborhood' => $request->neighborhood,
            'street' => $request->street,
            'building_number' => $request->building_number,
            'postal_code' => $request->postal_code,
            'extra_figure' => $request->extra_figure,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'step' => 3,
        ];

        if ($request->hasFile('image_address')) {
            $data['image_address'] = $request->file('image_address')->store('images/real_estates', 'public');
        }

        $realEstate->update($data);

        return response()->json([
            'message' => trans('api.updated_success'),
            'code' => 200,
            'success' => true,
            'data' => new Step2RealEstateResource($realEstate->fresh()),
        ]);
    }

    public function updateStep3(Request $request)
    {
        $request = $this->toStep3RealEstateRequest($request);
        $user = Auth::user();
        $realEstate = RealEstate::where('user_id', $user->id)->findOrFail($request->id);

        $data = [
            'name_real_estate' => $request->input('name_real_estate'),
            'name_owner' => $request->name_owner,
            'type_dob_property_owner' => $request->input('type_dob_property_owner', 'hijri'),
            //'property_owner_id_num' => $request->property_owner_id_num,
            'property_owner_dob_hijri' => HijriDobParts::combine(
                $request->property_owner_dob_day,
                $request->property_owner_dob_month,
                $request->property_owner_dob_year
            ),
            'property_owner_mobile' => $request->property_owner_mobile,
            'property_owner_iban' => $request->property_owner_iban,
            'add_legal_agent_of_owner' => $request->add_legal_agent_of_owner,
            'step' => 4,
        ];

        $hasAgent = in_array((string) $request->add_legal_agent_of_owner, ['1', 'true'], true)
            || $request->add_legal_agent_of_owner === 1
            || $request->add_legal_agent_of_owner === true;

        if ($hasAgent) {
            $data['id_num_of_property_owner_agent'] = $request->id_num_of_property_owner_agent;
            $data['type_dob_property_owner_agent'] = $request->input('type_dob_property_owner_agent', 'hijri');
            $data['dob_of_property_owner_agent'] = HijriDobParts::combine(
                $request->input('dob_of_property_owner_agent_day'),
                $request->input('dob_of_property_owner_agent_month'),
                $request->input('dob_of_property_owner_agent_year')
            );
            $data['mobile_of_property_owner_agent'] = $request->mobile_of_property_owner_agent;
            $data['agency_number_in_instrument_of_property_owner'] = $request->agency_number_in_instrument_of_property_owner;
            $data['type_agency_instrument_date_of_property_owner'] = $request->input(
                'type_agency_instrument_date_of_property_owner',
                'hijri'
            );
            $data['agency_instrument_date_of_property_owner'] = DateInputNormalizer::combineFromParts(
                $request->input('agency_instrument_date_of_property_owner_day'),
                $request->input('agency_instrument_date_of_property_owner_month'),
                $request->input('agency_instrument_date_of_property_owner_year')
            );
            if ($request->hasFile('copy_of_the_authorization_or_agency')) {
                $data['copy_of_the_authorization_or_agency'] = $request->file('copy_of_the_authorization_or_agency')
                    ->store('authorizations', 'public');
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
            'message' => trans('api.updated_success'),
            'code' => 200,
            'success' => true,
            'data' => new Step3RealEstateResource($realEstate->fresh()),
        ]);
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
