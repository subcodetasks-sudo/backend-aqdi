@extends('website.RealEstate.app')

@section('title', 'العنوان الوطني')

@section('content')

    <h3 class="heading3-state">رحلتك الإيجارية أصبحت أسهل</h3>
    <h1 class="heading1-state">العنوان الوطني للعقار</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    
    <form class="form-content-state" action="{{ route('realestate.step3.update', $realEstate->id) }}" method="POST" id="nationaladdress">
        @csrf
        <div class="form-group-state">
            <label for="region" class="label-state">المنطقة</label>
            <select id="property_place" class="select-state" name="property_place_id" onchange="updateCities()">
                <option value="" disabled selected>اختر المنطقة</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" {{ old('property_place_id', $realEstate->property_place_id) == $region->id ? 'selected' : '' }}>
                        {{ $region->name_trans }}
                    </option>
                @endforeach
            </select>
            @error('property_place_id')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
       
        <div class="form-group-state">
            <label for="city" class="label-state">المدينة</label>
            <select class="select-state" id="city" name="property_city_id">
                <option value="" disabled selected>{{ __('اختر المدينة') }}</option>
            </select>
            @error('property_city_id')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state">
            <label for="neighborhood" class="label-state">الحي</label>
            <input id="neighborhood" class="input-state" type="text" name="neighborhood" value="{{ old('neighborhood', $realEstate->neighborhood) }}" placeholder="الحي" />
            @error('neighborhood')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state">
            <label for="street-name" class="label-state">اسم الشارع</label>
            <input id="street-name" class="input-state" type="text" name="street" value="{{ old('street', $realEstate->street) }}" placeholder="اسم الشارع" />
            @error('street')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="row-state">
            <div class="form-group-state">
                <label for="building-number" class="label-state">رقم المبنى</label>
                <input id="building-number" class="input-state" type="number" name="building_number" value="{{ old('building_number', $realEstate->building_number) }}" placeholder="رقم المبنى" />
                @error('building_number')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group-state">
                <label for="postal-code" class="label-state">الرمز البريدي</label>
                <input id="postal-code" class="input-state" type="number" name="postal_code" value="{{ old('postal_code', $realEstate->postal_code) }}" placeholder="الرمز البريدي" />
                @error('postal_code')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group-state">
            <label for="additional-number" class="label-state">الرقم الإضافي</label>
            <input id="additional-number" class="input-state" type="number" name="extra_figure" value="{{ old('extra_figure', $realEstate->extra_figure) }}" placeholder="الرقم الإضافي" />
            @error('extra_figure')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="buttons-state">
            <a href="{{ url()->previous() }}" class="back-button-state">عودة</a>
            <button type="submit" class="next-button-state">
                التالي <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </div>
    </form>

    <a class="help-text-state" href="https://wa.me/yourwhatsapplink" target="_blank">
        واجهتك مشكلة؟ كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp" />
    </a>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            function updateCities() {
                var regionId = $('#property_place').val();
    
                $.ajax({
                    url: '/get-cities',
                    method: 'GET',
                    data: {
                        regionId: regionId
                    },
                    success: function(response) {
                        var cities = response.cities;
                        var cityDropdown = $('#city');
                        cityDropdown.empty();
    
                        if (cities.length > 0) {
                            $.each(cities, function(index, city) {
                                cityDropdown.append('<option value="' + city.id + '">' + city.name_trans + '</option>');
                            });
                        } else {
                            cityDropdown.append('<option value="none" disabled selected>{{ __('website.city') }}</option>');
                        }
    
                        var oldCityId = '{{ old('property_city_id', $realEstate->property_city_id) }}';
                        if (oldCityId) {
                            cityDropdown.val(oldCityId);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }
    
            $('#property_place').change(function() {
                updateCities();
            });
    
            updateCities();
        });
    </script>
@endsection
