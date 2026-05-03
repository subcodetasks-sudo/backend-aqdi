 @extends('website.RealEstate.app')

 @section('title', 'الخطوة الأولى')

 @section('content')


     <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
     <h1 class="heading1-state">العنوان الوطني للعقار</h1>
     <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
     <form class="form-content-state" method="Post"
         action="{{ route('create.step2.realEstate', ['id' => $realEstate->id]) }}">
         @csrf
         <div class="row-state">
             <div class="form-group-state">
         
                
                     <label for="region" class="label-state">المنطقة</label>
                    <select id="property_place" class="select-state" name="property_place_id" onchange="updateCities()">
                        <option value="" disabled {{ old('property_place_id') ? '' : 'selected' }}>اختر المنطقة</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ old('property_place_id') == $region->id ? 'selected' : '' }}>
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
                    <select id="city" class="select-state" name="property_city_id">
                        <option value="" disabled {{ old('property_city_id') ? '' : 'selected' }}>اختر المدينة</option>
                        @if(old('property_place_id') && isset($cities))
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" {{ old('property_city_id') == $city->id ? 'selected' : '' }}>
                                    {{ $city->name_trans }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('property_city_id')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                


         </div>

         <div class="form-group-state">
             <label for="neighborhood" class="label-state">الحي</label>
             <input id="neighborhood" class="input-state" type="text" value="{{old('neighborhood')}}" name="neighborhood" placeholder="الحي" />
             @error('neighborhood')
             <div class="error-message">{{ $message }}</div>
         @enderror
            </div>
         <div class="form-group-state">
             <label for="street-name" class="label-state">اسم الشارع</label>
             <input id="street-name" class="input-state" type="text"  value="{{old('street')}}" name="street" placeholder="اسم الشارع" />
             @error('street')
             <div class="error-message">{{ $message }}</div>
         @enderror

            </div>

         <div class="row-state">
             <div class="form-group-state">
                 <label for="building-number" class="label-state">رقم المبنى</label>
                 <input id="building-number" class="input-state" type="number" value="{{old('building_number')}}"   name="building_number"
                     placeholder="رقم المبنى" />
                     @error('building_number')
                <div class="error-message">{{ $message }}</div>
            @enderror

             </div>
             <div class="form-group-state">
                 <label for="postal-code" class="label-state">الرمز البريدي</label>
                 <input id="postal-code" class="input-state" type="number" value="{{old('postal_code')}}"  name="postal_code"
                     placeholder="الرمز البريدي" />
                     @error('postal_code')
                     <div class="error-message">{{ $message }}</div>
                 @enderror
             </div>
            
         </div>

         <div class="form-group-state">
             <label for="additional-number" class="label-state">الرقم الإضافي</label>
             <input id="additional-number" class="input-state" type="number" value="{{old('extra_figure')}}"  name="extra_figure"
                 placeholder="الرقم الإضافي" />
                 @error('extra_figure')
                <div class="error-message">{{ $message }}</div>
            @enderror
         </div>

         <div class="buttons-state">
             <a type="button" href="{{ url()->previous() }}" class="back-button-state">عودة</a>
             
             <button type="submit" class="next-button-state">
                 التالي
                 <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="Next" />
             </button>
         </div>
     </form>
     </section>
     <a class="help-text-state" href="">
         واجهتك مشكلة ؟كلمنا على واتساب
         <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
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

                         cityDropdown.append('<option value="" disabled selected>اختر المدينة</option>');

                         if (cities.length > 0) {
                             $.each(cities, function(index, city) {
                                 cityDropdown.append('<option value="' + city.id + '">' + city
                                     .name_trans + '</option>');
                             });
                         } else {
                             cityDropdown.append(
                                 '<option value="none" disabled selected>لا توجد مدن متاحة</option>');
                         }
                     },
                     error: function(xhr, status, error) {
                         console.error('Error fetching cities:', error);
                     }
                 });
             }

             $('#property_place').change(function() {
                 updateCities();
             });

             if ($('#property_place').val()) {
                 updateCities();
             }
         });
     </script>


 @endsection
