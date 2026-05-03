<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>عقاراتي</title>
    <link rel="icon" href="{{ asset('website/asset/images/logo.png') }}" type="image/svg+xml" />
    <link rel="stylesheet" href="{{ asset('website/asset/css/style.css') }}" />
    <!-- FontAwesome CDN Link -->
    <script src="https://kit.fontawesome.com/5ef60b71ad.js" crossorigin="anonymous"></script>
</head>

<body>
    <section class="real-state-form">
        <h1 class="heading1-state">بيانات الوحدة المؤجرة</h1>
        <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
        <form class="form-content-state" action="{{ route('store.Unit',['id'=>$unitReal->id]) }}" method="POST">
            @csrf
            <div class="row-state">
               
                <div class="form-group-state">
                    <label for="unit-name" class="label-state">نوع الوحده</label>
                    <select id="unit-name" class="select-state " name="unit_type_id">
                        <option value="" disabled selected hidden>نوع الوحده</option>
                        @foreach ($unitType as $item)
                        <option value="{{ $item->id }}" @if(old('unit_type_id') == $item->id) selected @endif>{{ $item->name_trans }}</option>
                    @endforeach
                    </select>
                </div>
                
                            <div class="form-group-state">
                <label for="unit-usage" class="label-state">استخدام الوحده</label>
                <select id="unit-usage" class="select-state " name="unit_usage_id">
                     @foreach ($unitUsage as $item)
                    <option value="{{ $item->id }}" @if(old('unit_usage_id') == $item->id) selected @endif>{{ $item->name_ar }}</option>
                @endforeach
                </select>
            </div>
            </div>



            <div class="row-state">
                <div class="form-group-state">
                    <label for="unit-number" class="label-state">رقم الوحده</label>
                    <select type="text" id="unit-number" class="select-state " name="unit_number"
                        placeholder="رقم الوحده">
                        <option value="" disabled selected hidden>رقم الوحده</option>

                        @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                    </select>
                </div>
                <div class="form-group-state">
                    <label for="floor-number" class="label-state">رقم الطابق</label>
                    <select type="text" id="floor-number" class="select-state " name="floor_number"
                        placeholder="رقم الطابق">
                        <option value="" disabled selected hidden>رقم الطابق</option>

                        @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor

                      </select>
                </div>
            </div>

            <div class="form-group-state">
                <label for="unit-area" class="label-state">مساحة الوحدة الاجمالي تقريبا</label>
                <input type="text" id="unit-area" class="input-state" name="unit_area"
                    placeholder="مساحة الوحدة الاجمالي تقريبا">
 
            </div>
             @if($unitReal->contract_type=='commercial')
            
            <div class="form-group-state apartment-dependent-none">
                <label for="subleasing" class="label-state">التأجير من الباطن</label>
                <select id="subleasing" class="select-state " name="sub_delay">
                    <option value="" disabled selected>التأجير من الباطن</option>
                    <option value="1">نعم</option>
                    <option value="0">لا</option>
                </select>
            </div>
          @endif
            @if($unitReal->contract_type=='housing')
            <div class="row-state apartment-dependent" style="">
                <div class="form-group-state">
                    <label for="rooms-number" class="label-state">عدد الغرف</label>
                    <select id="rooms-number" class="select-state " name="tootal_rooms">
                        <option value="" disabled selected hidden>عدد الغرف </option>
                        <option value="0">لا يوجد</option>
                      @for ($i = 1; $i <= 10; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor
                
                </select>
                </div>
                <div class="form-group-state">
                    <label for="halls-number" class="label-state">عدد الصالات</label>
                    <select id="halls-number" class="select-state " name="The_number_of_halls">
                        <option value="" disabled selected hidden>عدد الصالات </option>
                        <option value="0">لا يوجد</option>
                      @for ($i = 1; $i <= 10; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                     @endfor
                    </select>
                </div>
            </div>

            <div class="row-state apartment-dependent" style="">
                <div class="form-group-state">
                    <label for="kitchens-number" class="label-state">عدد المطابخ</label>
                    <select id="kitchens-number" class="select-state " name="The_number_of_kitchens">
                        <option value="" disabled selected hidden>عدد المطابخ </option>
                        <option value="0">لا يوجد</option>
                      @for ($i = 1; $i <= 10; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                  @endfor

                    </select>
                </div>
                <div class="form-group-state">
                    <label for="bathrooms-number" class="label-state">عدد دورات المياه</label>
                    <select id="bathrooms-number" class="select-state " name="The_number_of_toilets">
                        <option value="" disabled selected hidden>عدد دورات المياه  </option>
                        <option value="0">لا يوجد</option>
                      @for ($i = 1; $i <= 10; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                      @endfor
                    </select>
                </div>
            </div>
            @endif
            <div class="row-state">
                <div class="form-group-state">
                    <label for="window-ac" class="label-state">مكيف شباك</label>
                    <select id="window-ac" class="select-state " name="window_ac">
                        <option value="" disabled selected hidden>عدد مكيف شباك  </option>
                        <option value="0">لا يوجد</option>
                      @for ($i = 1; $i <= 10; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                     @endfor

                    </select>
                </div>
                <div class="form-group-state">
                    <label for="split-ac" class="label-state">مكيف سبليت</label>
                    <select id="split-ac" class="select-state " name="split_ac">
                        <option value="" disabled selected hidden>عدد مكيف شباك  </option>
                        <option value="0">لا يوجد</option>
                      @for ($i = 1; $i <= 10; $i++)
                      <option value="{{ $i }}">{{ $i }}</option>
                     @endfor
                    </select>
                </div>
            </div>

            <div class="row-state">
                <div class="form-group-state">
                    <label for="electricity-meter" class="label-state">رقم عداد الكهرباء (اختياري)</label>
                    <input id="electricity-meter" class="input-state" placeholder="رقم عداد الكهرباء (اختياري)" name="electricity_meter_number">
                        
                     
                </div>
                <div class="form-group-state">
                    <label for="water-meter" class="label-state">رقم عداد المياه (اختياري)</label>
                    <input id="water-meter" class="input-state" placeholder="رقم عداد المياه (اختياري)" name="water_meter_number">
                    
                </div>
            </div>

            <div class="buttons-state">
                <a type="button" href="{{ url()->previous() }}" class="back-button-state">عودة</a>
                <button type="submit" class="next-button-state">
                    حفظ
                    <i class="fa-solid fa-arrow-left-long"></i>
                </button>
            </div>
        </form>
    </section>
    <div class="overlay"></div>
    <div class="info-box-add-unit">
        <img src="{{ asset('website/asset/images/success-icon.svg') }}" alt="" />
        <h2>تم اضافة بيانات الوحدة بنجاح</h2>
        <p>
            تستطيع الان انشاء عقود الايجار بكل سهولة ودون تعبئة بيانات العقار
            والمالك
        </p>
        <div class="links-buttons-action">
            <a href="#">عرض العقارات</a>
            <a href="#">اضافة وحدة على هذا العقار</a>
            <a href="#">
                انشاء عقد ايجار على هذا العقار
                <i class="fa-solid fa-arrow-left-long"></i>
            </a>
        </div>
    </div>
    <a class="help-text-state" href="">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
    </a>
    {{-- <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("#unit-solo-form");
            const selectElements = {
                unitType: document.getElementById("unit-type"),
                unitUsage: document.getElementById("unit-usage"),
                unitNumber: document.getElementById("unit-number"),
                floorNumber: document.getElementById("floor-number"),
                unitArea: document.getElementById("unit-area"),
                subleasing: document.getElementById("subleasing"),
                windowAc: document.getElementById("window-ac"),
                splitAc: document.getElementById("split-ac"),
                electricityMeter: document.getElementById("electricity-meter"),
                waterMeter: document.getElementById("water-meter"),
            };

            const savedData = JSON.parse(localStorage.getItem("unit-solo-form"));
            if (savedData) {
                Object.keys(selectElements).forEach((key) => {
                    if (savedData[key]) {
                        selectElements[key].value = savedData[key];
                    }
                });
            }

            // form.addEventListener("submit", function(event) {
            //     event.preventDefault();

            //     const formData = {};
            //     Object.keys(selectElements).forEach((key) => {
            //         formData[key] = selectElements[key].value;
            //     });
            //     localStorage.setItem("unit-solo-form", JSON.stringify(formData));

            //     const overlay = document.querySelector(".overlay");
            //     const infoBox = document.querySelector(".info-box-add-unit");

            //     overlay.style.display = "block";
            //     infoBox.style.display = "block";

            //     setTimeout(() => {
            //         overlay.style.display = "none";
            //         infoBox.style.display = "none";
            //     }, 3000);
            // });

            Object.keys(selectElements).forEach((key) => {
                selectElements[key].addEventListener("change", function() {
                    const formData = {};
                    Object.keys(selectElements).forEach((key) => {
                        formData[key] = selectElements[key].value;
                    });
                    localStorage.setItem("unit-solo-form", JSON.stringify(formData));
                });
            });

            const unitTypeSelect = document.getElementById("unit-type");
            const apartmentDependentDivs = document.querySelectorAll(
                ".apartment-dependent"
            );
            const apartmentDependentNoneDiv = document.querySelector(
                ".apartment-dependent-none"
            );

            function handleUnitTypeChange() {
                const selectedValue = unitTypeSelect.value;

                if (selectedValue === "apartment") {
                    apartmentDependentDivs.forEach((div) => {
                        div.style.display = "flex";
                    });
                    apartmentDependentNoneDiv.style.display = "none";
                } else {
                    apartmentDependentDivs.forEach((div) => {
                        div.style.display = "none";
                    });
                    apartmentDependentNoneDiv.style.display = "flex";
                }
            }

            if (unitTypeSelect) {
                unitTypeSelect.addEventListener("change", handleUnitTypeChange);
                handleUnitTypeChange();
            }
        });
    </script> --}}
</body>

</html>
