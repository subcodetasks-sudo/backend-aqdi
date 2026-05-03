@extends('website.RealEstate.app')

@section('title', 'الخطوة الثالثه')

@section('content')




    <h3 class="heading3-state">رحلتك الإيجارية أصبحت أسهل</h3>
    <h1 class="heading1-state">بيانات مالك العقار</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    <form method="POST" action="{{ route('storeStep3.realEstate', ['id' => $realEstate->id]) }}" class="form-content-state" id="owner-form">
        @csrf
        <div class="form-group-state">
            <label for="property-owner-name" class="label-state">اسم مالك العقار</label>
            <input id="property-owner-name" class="input-state" type="text" name="name_owner"
                value="{{ old('name_owner') }}" placeholder="اسم مالك العقار" />
            @error('name_owner')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state">
          <label for="property-owner-name" class="label-state">اسم العقار</label>
          <input id="property-owner-name" class="input-state" type="text" name="name_real_estate"
              value="{{ old('name_real_estate') }}" placeholder="اسم  العقار" />
          @error('name_real_estate')
              <div class="error-message">{{ $message }}</div>
          @enderror
      </div>

        
        <div class="row-state">
            <div class="form-group-state">
                <label for="owner-id-number" class="label-state">رقم هوية المالك</label>
                <input id="owner-id-number" class="input-state owner-number" type="text" name="property_owner_id_num"
                    value="{{ old('property_owner_id_num') }}" placeholder="رقم هوية المالك" />
                @error('property_owner_id_num')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group-state">
              <label for="owner-birthdate" class="label-state">تاريخ ميلاد المالك</label>
              <input id="owner-birthdate" class="input-state hijri-date-picker date-picker" type="text" name="property_owner_dob_hijri"
                  value="{{ old('property_owner_dob_hijri') }}" placeholder="تاريخ ميلاد المالك" />
              @error('property_owner_dob_hijri')
                  <div class="error-message">{{ $message }}</div>
              @enderror
          </div>
          
          <script>
              // Example of initializing a Hijri date picker
              $('.hijri-date-picker').hijriDatePicker({
                  hijri: true, // Enable Hijri mode
                  format: 'DD-MM-YYYY' // Customize format
              });
          </script>
          
        </div>

        <div class="form-group-state">
            <label for="owner-phone" class="label-state">رقم جوال المالك 05xxxxxxxx</label>
            <input id="owner-phone" class="input-state phone" type="text" name="property_owner_mobile" value="{{ old('property_owner_mobile') }}"
                placeholder="رقم جوال المالك 05xxxxxxxx" />
            @error('property_owner_mobile')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state owner-iban">
            <label for="owner-iban" class="label-state">رقم الآيبان البنكي للمالك المكون من 22 رقم</label>
            <input id="owner-iban" class="input-state" type="text" name="property_owner_iban"
                placeholder="رقم الآيبان البنكي للمالك المكون من 22 رقم" />
        </div>
                    @error('property_owner_iban')
                <div class="error-message">{{ $message }}</div>
            @enderror

        <div class="form-group-state">
            <label for="add-agent" class="label-state">هل تود أن تضيف وكيل لمالك العقار؟</label>
            <select id="add-agent" class="select-state" name="add_legal_agent_of_owner">
                <option value="" disabled {{ old('add_legal_agent_of_owner') === null ? 'selected' : '' }}>هل تود أن
                    تضيف وكيل لمالك العقار؟</option>
                <option value="1" {{ old('add_legal_agent_of_owner') === 'yes' ? 'selected' : '' }}>نعم</option>
                <option value="0" {{ old('add_legal_agent_of_owner') === 'no' ? 'selected' : '' }}>لا</option>
            </select>
            @error('add_legal_agent_of_owner')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <h2 class="sub-header-state" style="display: none" id="agent-header">
            معلومات الوكيل
        </h2>

        <div class="form-group-state" id="agent-number" style="display: none">
            <label for="agent-id-number" class="label-state">رقم هوية وكيل المالك</label>
            <input id="agent-id-number" class="input-state owner-number" type="text"
                name="id_num_of_property_owner_agent" placeholder="رقم هوية وكيل المالك"
                value="{{ old('id_num_of_property_owner_agent') }}" />
            @error('id_num_of_property_owner_agent')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state" style="display: none" id="agent-birth">
            <label for="agent-birthdate" class="label-state">تاريخ ميلاد وكيل المالك</label>
            <input id="agent-birthdate" class="input-state date-picker" type="text" name="dob_of_property_owner_agent"
                placeholder="تاريخ ميلاد وكيل المالك" value="{{ old('dob_of_property_owner_agent') }}" />
            @error('dob_of_property_owner_agent')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state" style="display: none" id="agent-phone-number">
            <label for="agent-phone" class="label-state">رقم جوال وكيل المالك</label>
            <input id="agent-phone" class="input-state phone" type="text" name="mobile_of_property_owner_agent"
                placeholder="رقم جوال وكيل المالك" value="{{ old('mobile_of_property_owner_agent') }}" />
            @error('mobile_of_property_owner_agent')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="row-state" style="display: none" id="agency-number-agent">
            <div class="form-group-state">
                <label for="agency-date" class="label-state">تاريخ الوكالة</label>
                <input id="agency-date" class="input-state date-picker" type="text"
                    name="agency_instrument_date_of_property_owner" placeholder="تاريخ الوكالة"
                    value="{{ old('agency_instrument_date_of_property_owner') }}" />
                @error('agency_instrument_date_of_property_owner')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group-state">
                <label for="agency-number" class="label-state">رقم الوكالة</label>
                <input id="agency-number" class="input-state" type="number" name="agency_number_in_instrument_of_property_owner"
                    placeholder="رقم الوكالة" value="{{ old('agency_number_in_instrument_of_property_owner') }}" />
                @error('agency_number_in_instrument_of_property_owner')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="buttons-state">
            <a type="button" href="form-2.html" class="back-button-state">عودة</a>
            <button type="submit" class="next-button-state">
                حفظ
                <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </div>
    </form>

    <a class="help-text-state" href="https://wa.me/+966597500014">
        واجهتك مشكلة؟ كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
    </a>

 
 
    <script>
      $(function () {
        initDatePicker("hijri");
        $("#calendar-switcher").on("change", function () {
          const selectedCalendar = $(this).val();
          initDatePicker(selectedCalendar);
        });
      });

      function initDatePicker(calendarType) {
        $("#agent-birthdate").hijriDatePicker("destroy");
        $("#owner-birthdate").hijriDatePicker("destroy");
        $("#agency-date").hijriDatePicker("destroy");
        if (calendarType === "hijri") {
          $("#agent-birthdate").hijriDatePicker({
            hijri: true,
            showSwitcher: false,
          });
          $("#owner-birthdate").hijriDatePicker({
            hijri: true,
            showSwitcher: false,
          });
          $("#agency-date").hijriDatePicker({
            hijri: true,
            showSwitcher: false,
          });
        } else {
          $("#agent-birthdate").hijriDatePicker({
            hijri: false,
            showSwitcher: false,
          });
          $("#owner-birthdate").hijriDatePicker({
            hijri: false,
            showSwitcher: false,
          });
          $("#agency-date").hijriDatePicker({
            hijri: false,
            showSwitcher: false,
          });
        }
      }
    </script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const addAgentSelect = document.getElementById("add-agent");
        const agentHeader = document.getElementById("agent-header");
        const agentNumberDiv = document.getElementById("agent-number");
        const agentBirthDiv = document.getElementById("agent-birth");
        const agentPhoneNumberDiv =
          document.getElementById("agent-phone-number");
        const agencyNumberAgentDiv = document.getElementById(
          "agency-number-agent"
        );

        function handleAgentSelection() {
          const selection = addAgentSelect.value;

          if (selection === "1") {
            agentHeader.style.display = "block";
            agentNumberDiv.style.display = "flex";
            agentBirthDiv.style.display = "flex";
            agentPhoneNumberDiv.style.display = "flex";
            agencyNumberAgentDiv.style.display = "flex";
          } else if (selection === "0") {
            agentHeader.style.display = "none";
            agentNumberDiv.style.display = "none";
            agentBirthDiv.style.display = "none";
            agentPhoneNumberDiv.style.display = "none";
            agencyNumberAgentDiv.style.display = "none";
          }
        }

        addAgentSelect.addEventListener("change", handleAgentSelection);

  

        function saveFormData() {
          const form = document.getElementById("owner-form");
          const addingStateOwner = {};
          const inputs = form.querySelectorAll("input, select");

          inputs.forEach((input) => {
            addingStateOwner[input.name] = input.value;
          });

          localStorage.setItem(
            "addingStateOwner",
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

        // function handleFormSubmission(event) {
        //   event.preventDefault();
        //   saveFormData();
        //   localStorage.clear();
        //   window.location.href = "form-end.html";
        // }

        const form = document.querySelector("#owner-form");
        // form.addEventListener("submit", handleFormSubmission);

        function populateFormFields() {
          const savedData = JSON.parse(
            localStorage.getItem("addingStateOwner")
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

                  // Dispatch change event on page load
        const event = new Event("change");
        addAgentSelect.dispatchEvent(event);
        
            if (savedData["add-agent"] === "yes") {
              handleAgentSelection();
            }
          }
        }

        populateFormFields();
      });
    </script>
@endsection
