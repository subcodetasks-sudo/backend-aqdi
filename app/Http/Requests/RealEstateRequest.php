<?php

namespace App\Http\Requests;

use App\Rules\CheckAge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RealEstateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
            return [
                'instrument_type' => 'required|in:electronic,strong_argument',
                'instrument_number' => [Rule::requiredIf($this->input('instrument_type') == 'electronic')],
                 'instrument_history' => ['nullable', 'date', Rule::requiredIf($this->input('instrument_type') == 'electronic')],
                 'real_estate_registry_number' => [Rule::requiredIf($this->input('instrument_type') == 'strong_argument')],
                'date_first_registration' => [Rule::requiredIf($this->input('instrument_type') == 'strong_argument')],
                'name_owner' => 'required',
                'national_num' => 'required|max:10',
                'number_of_units_in_realestate'=>'required',
                'mobile' => 'required|max:12',
                
                'iban_bank' =>[
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!preg_match('/^[A-Z]{2}\d{22}$/', $value)) {
                            return $fail($this->messages()['iban_bank.regex']);
                        }
                    },
                ],
                'name_real_estate' => 'required',
                'property_type_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if ($value != 0 && !\App\Models\ReaEstatType::where('id', $value)->exists()) {
                            $fail('The selected property type is invalid.');
                        }
                    },
                ],
              
                'property_usages_id' => 'required|exists:rea_estat_usages,id',
                'property_place_id' => 'required|exists:regions,id',
                'property_city_id' => 'required|exists:cities,id',
                'street' => 'required',
                'postal_code' => 'required',
                'dob_hijri'=> ['required', new CheckAge],
                'extra_figure' => 'required',
                'neighborhood' => 'required',
                'number_of_floors' => 'required',
                'building_number' => 'required',

                
                
            ];
            
        }
    
        public function messages()
        {
            return [
                'name_real_estate.required' => 'اسم العقار مطلوب',
                'name_owner.required' => 'اسم صاحب العقار مطلوب',
                'national_num.required' => 'رقم الهوية مطلوب',
                'mobile.required' => 'رقم الجوال مطلوب',
                'dob_hijri.before'=>'عمر المالك لا يقل عن 18 عام ',
                'mobile.max' => 'رقم الجوال لا يزيد عن 12 رقم',
                'national_num.max' => 'رقم الهوية لا يزيد عن 10 ارقام',
                'iban_bank.required' => 'االيبان البنكي لمالك العقار مطلوب.',
                'iban_bank.regex' => 'الايبان البنكي لمالك العقار يجب أن يبدأ بحرفين متبوع بـ 22 رقم فقط.',
                'iban_bank.max' => 'الايبان البنكي لا يزيد عن 24 رقم',
                'number_of_floors.required' => 'عدد الطوابق مطلوب',
                'property_usages_id.required' => 'أستخدام العقار مطلوب',
                'property_type_id.required' => 'نوع العقار مطلوب',
                'number_of_units_in_realestate'=>'عدد الوحدات مطلوب',
                'photo_of_the_electronic'=>'صورة العقد الالكتروني مطلوبه',
                  'street.required'=>'اسم الشارع مطلوب',
                'dob_hijri.required'=>'تاريخ الميلاد الهجري مطلوب',
            ];
        }
 
}
