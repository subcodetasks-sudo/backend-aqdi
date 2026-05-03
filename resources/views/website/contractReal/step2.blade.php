@extends('website.Contract.layout.app')

@section('title', 'الخطوة الثانية')

@section('content')

    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">العنوان الوطني للعقار</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    
    <form method="POST" action="{{ route('real.submit.step2',['uuid' => $contract->uuid, 'real_id' => $real->id,'id' => $unit->id]) }}" class="form-content-state">
        @csrf
        
            
        <div class="form-group-state">
            @error('property_place_id')
            <div class="error-message">{{ $message }}</div>
            @enderror
            <label for="property_place" class="label-state">المنطقة</label>
            <select id="property_place" class="select-state" name="property_place_id" onchange="updateCities()">
                <option value="" disabled {{ old('property_place_id', $real->property_place_id) == '' ? 'selected' : '' }}>اختر المنطقة</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" {{ old('property_place_id', $real->property_place_id) == $region->id ? 'selected' : '' }}>
                        {{ $region->name_trans }}
                    </option>
                @endforeach
            </select>
        </div>
        @error('property_place_id')
        <div class="error-message">{{ $message }}</div>
        @enderror
        
      <div class="form-group-state">
            @error('property_city_id')
            <div class="error-message">{{ $message }}</div>
            @enderror
            <label for="city" class="label-state">المدينة</label>
                  <select class="select-state" id="city" name="property_city_id"></select>
             </select>
        </div>
        @error('property_city_id')
        <div class="error-message">{{ $message }}</div>
        @enderror
        <!-- Neighborhood -->
        <div class="form-group-state">
            @error('neighborhood')
            <div class="error-message">{{ $message }}</div>
            @enderror
            <label for="neighborhood" class="label-state">الحي</label>
            <input id="neighborhood" class="input-state" type="text" name="neighborhood"  required placeholder="الحي" value="{{ old('neighborhood', $real->neighborhood) }}" />
        </div>
        @error('neighborhood')
        <div class="error-message">{{ $message }}</div>
        @enderror
        <!-- Street -->
        <div class="form-group-state">
            <label for="street-name" class="label-state">اسم الشارع</label>
            <input id="street-name" class="input-state" type="text" name="street" required placeholder="اسم الشارع" value="{{ old('street', $real->street) }}" />
        </div>
        @error('street')
        <div class="error-message">{{ $message }}</div>
        @enderror
    
        <!-- Building Number -->
        <div class="form-group-state">
            @error('building_number')
            <div class="error-message">{{ $message }}</div>
            @enderror
            <label for="building-number" class="label-state">رقم المبنى</label>
            <input id="building-number" class="input-state" type="number" name="building_number" required placeholder="رقم المبنى" value="{{ old('building_number', $real->building_number) }}" />
        </div>
        @error('building_number')
        <div class="error-message">{{ $message }}</div>
        @enderror
        <!-- Postal Code -->
        <div class="form-group-state">
            @error('postal_code')
            <div class="error-message">{{ $message }}</div>
            @enderror
            <label for="postal-code" class="label-state">الرمز البريدي</label>
            <input id="postal-code" class="input-state" type="number" name="postal_code"  required value="{{ old('postal_code', $real->postal_code) }}" placeholder="الرمز البريدي" />
        </div>
        @error('postal_code')
        <div class="error-message">{{ $message }}</div>
        @enderror
        <!-- Extra Figure -->
        <div class="form-group-state">
            @error('extra_figure')
            <div class="error-message">{{ $message }}</div>
            @enderror
            <label for="additional-number" class="label-state">الرقم الإضافي</label>
            <input id="additional-number" class="input-state" type="number" name="extra_figure"  required value="{{ old('extra_figure', $real->extra_figure) }}" placeholder="الرقم الإضافي" />
        </div>
        @error('extra_figure')
        <div class="error-message">{{ $message }}</div>
        @enderror
        <!-- Buttons -->
        <div class="buttons-state">
            <a href="{{ url()->previous() }}" class="back-button-state">عودة</a>

            <button type="submit" class="next-button-state">التالي</button>
        </div>
    </form>
    
    <!-- Help Text -->
    <a class="help-text-state" href="">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp Icon" />
    </a>

    <!-- Modal Logic -->
    <div class="modal-overlay-died hidden" id="died-modal">
        <div class="modal-content-died">
            <div class="modal-header-died">
                <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-died" />
            </div>
            <div class="modal-body-died">
                <div class="modal-images-died">
                    <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="Ejar Icon" class="modal-image-died" />
                    <div class="separator-died"></div>
                    <img src="{{ asset('website/asset/images/logo.png') }}" alt="Logo" class="modal-image-died" />
                </div>
                <h1 class="modal-title-died">عفوا</h1>
                <p class="modal-text-died">
                    لانستطيع معالجة صك المتوفي حاليا<br />
                    الرجاء مراسلتنا عبر الواتساب ليقوم فريقنا بخدمتكم
                </p>
                <a href="https://wa.me/your-number" class="whatsapp-link-died">
                    ستضاف رسوم بقيمة 150 ريال لمعالجة صك المتوفي
                    <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="Arrow" class="arrow-icon-died" />
                </a>
            </div>
        </div>
    </div>

 
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
                            cityDropdown.append('<option value="' + city.id + '">' + city.name_trans +
                                '</option>');
                        });
                    } else {
                         cityDropdown.append('<option value="none" disabled selected>{{__('website.city')}}</option>');
                    }

                     var oldCityId = '{{ old('
                    property_city_id ', $contract->property_city_id) }}';
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
