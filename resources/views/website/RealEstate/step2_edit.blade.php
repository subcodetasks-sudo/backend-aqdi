@extends('website.RealEstate.app')

@section('title', 'بيانات المالك ')

@section('content')
    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">بيانات مالك العقار</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    <form class="form-content-state" action="{{ route('realestate.step2.update', $realEstate->id) }}" method="POST" id="owner-form">
        @csrf
        <div class="form-group-state">
            <label for="property-owner-name" class="label-state">اسم مالك العقار</label>
            <input id="property-owner-name" class="input-state" type="text" name="name_owner"
                placeholder="اسم مالك العقار" value="{{ old('name_owner', $realEstate->name_owner) }}" />
               
              </div> 
              @error('name_owner')
              <div class="error-message">{{ $message }}</div>
          @enderror

        <div class="row-state">
            <div class="form-group-state">
                <label for="owner-id-number" class="label-state">رقم هوية المالك</label>
                <input id="owner-id-number" class="input-state owner-number" type="text" name="property_owner_id_num"
                    placeholder="رقم هوية المالك" value="{{ old('property_owner_id_num', $realEstate->property_owner_id_num) }}" />
                    
                  </div>
                  @error('property_owner_id_num')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            <div class="form-group-state">
                <label for="owner-birthdate" class="label-state">تاريخ ميلاد المالك</label>
                <input id="owner-birthdate" class="input-state date-picker" type="text" name="dob_hijri"
                    placeholder="تاريخ ميلاد المالك" value="{{ old('dob_hijri', $realEstate->dob_hijri) }}" />
                  
                  </div>
                  @error('dob_hijri')
                  <div class="error-message">{{ $message }}</div>
              @enderror

          </div>

        <div class="form-group-state">
            <label for="owner-phone" class="label-state">رقم جوال المالك 05xxxxxxxx</label>
            <input id="owner-phone" class="input-state phone" type="number" name="mobile"
                placeholder="رقم جوال المالك 05xxxxxxxx" value="{{ old('mobile', $realEstate->mobile) }}" />
               
              </div>
              @error('mobile')
              <div class="error-message">{{ $message }}</div>
          @enderror
        <div class="form-group-state owner-iban">
            <label for="owner-iban" class="label-state">رقم الايبان البنكي للمالك المكون من 22 رقم</label>
            <input id="owner-iban" class="input-state" type="number" name="iban_bank"
                placeholder="رقم الايبان البنكي للمالك المكون من 22 رقم" value="{{ old('iban_bank', $realEstate->iban_bank) }}" />
               
              </div>
              @error('iban_bank')
              <div class="error-message">{{ $message }}</div>
          @enderror
        <div class="buttons-state">
            <a type="button" href="form-2.html" class="back-button-state">عودة</a>
            <button type="submit" class="next-button-state">
                حفظ
                <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </div>
    </form>

    <a class="help-text-state" href="">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="/images/whatsapp-icon.svg" alt="" />
    </a>

    <script>
        $(function() {
            initDatePicker("hijri");
            $("#calendar-switcher").on("change", function() {
                const selectedCalendar = $(this).val();
                initDatePicker(selectedCalendar);
            });
        });

        function initDatePicker(calendarType) {
            $("#owner-birthdate").hijriDatePicker("destroy");
            if (calendarType === "hijri") {
                $("#owner-birthdate").hijriDatePicker({
                    hijri: true,
                    showSwitcher: false,
                });
            } else {
                $("#owner-birthdate").hijriDatePicker({
                    hijri: false,
                    showSwitcher: false,
                });
            }
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function saveFormData() {
                const form = document.getElementById("owner-form");
                const addingStateOwner = {};
                const inputs = form.querySelectorAll("input, select");

                inputs.forEach((input) => {
                    addingStateOwner[input.name] = input.value;
                });

                localStorage.setItem(
                    "addingStateOwner-edit",
                    JSON.stringify(addingStateOwner)
                );
            }

            const formElements = document.querySelectorAll(
                "#owner-form input, #owner-form select"
            );

            formElements.forEach((element) => {
                element.addEventListener("input", saveFormData);
                element.addEventListener("change", saveFormData);
            });

            function populateFormFields() {
                const savedData = JSON.parse(
                    localStorage.getItem("addingStateOwner-edit")
                );

                if (savedData) {
                    const inputs = document.querySelectorAll(
                        "#owner-form input, #owner-form select"
                    );

                    inputs.forEach((input) => {
                        if (savedData[input.name]) {
                            input.value = savedData[input.name];
                        }
                    });
                }
            }

            populateFormFields();
        });
    </script>
@endsection
