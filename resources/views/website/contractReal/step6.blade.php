@extends('website.Contract.layout.app')
@section('title', 'المالك')
@section('content')

    <!-- FontAwesome CDN Link -->
    <script
      src="https://kit.fontawesome.com/5ef60b71ad.js"
      crossorigin="anonymous"
    ></script>

<h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
<h1 class="heading1-state">البيانات المالية</h1>
<p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>

<form action="{{ route('submit.step6', [$contract->uuid]) }}" method="POST" class="form-content-state" id="fund-form">
    @csrf

    <div class="row-state contract-dependent">
        <div class="form-group-state">
            <label for="contract-start-date" class="label-state">تاريخ بدء العقد</label>
            <input id="contract-start-date" class="input-state date-picker" type="text"  name="contract_starting_date"
                value="{{ old('contract_starting_date', $contract->contract_starting_date) }}" placeholder="تاريخ بدء العقد" />
        </div>
    

    <div class="form-group-state">
                        <label
              for="contract-duration"
              class="label-state"
              style="display: flex; justify-content: space-between"
            >
              مدة العقد بالسنة
              <span class="clarify" id="clarify">توضيح</span>
            </label>
        <select
          id="contract-duration"
          class="select-state"
          name="contract_term_in_years"
        >
        <option value="" disabled selected>مدة العقد بالسنه</option>
        @foreach($contract_periods as $value)
        <option value="{{ $value->id }}" {{ old('contract_term_in_years', $contract->contract_term_in_years) == $value->id ? 'selected' : '' }}>
            {{ $value->period }}
        </option>
    @endforeach
      </select>
      </div>
    </div>
    <div class="form-group-state">
        <label for="annual-rent-amount" class="label-state">مبلغ الايجار بالسنوي</label>
        <input id="annual-rent-amount" class="input-state number-input" type="number"  name="annual_rent_amount_for_the_unit"
            value="{{ old('annual_rent_amount_for_the_unit', $contract->annual_rent_amount_for_the_unit) }}"
            placeholder="مبلغ الايجار بالسنوي" />
    </div>

    <div class="form-group-state">
        <label for="payment-method" class="label-state">طريقة الدفعات</label>
        <select id="payment-method" class="select-state" name="payment_type_id" >
            <option value="" disabled selected>طريقة الدفعات</option>
            @foreach($payment_types as $value)
                <option value="{{ $value->id }}" {{ old('payment_type_id', $contract->payment_type_id) == $value->id ? 'selected' : '' }}>
                    {{ $value->name_trans }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group-state">
        <label for="additional-conditions" class="label-state">هل تود اضافة شروط اخرى؟</label>
        <select id="additional-conditions" class="select-state" name="additional_conditions" onchange="toggleAdditionalConditions()">
            <option value="" disabled {{ old('additional_conditions', $contract->additional_conditions) == '' ? 'selected' : '' }}>هل تود اضافة شروط اخرى؟</option>
            <option value="yes" {{ old('additional_conditions', $contract->additional_conditions) == 'yes' ? 'selected' : '' }}>نعم</option>
            <option value="no" {{ old('additional_conditions', $contract->additional_conditions) == 'no' ? 'selected' : '' }}>لا</option>
        </select>
    </div>
    
    <div class="form-group-state additional-conditions-textarea" id="additional-conditions-section" style="{{ old('additional_conditions', $contract->additional_conditions) == 'yes' ? 'display: flex;' : 'display: none;' }}">
        <label for="additional-conditions-text" class="label-state">الشروط الإضافية</label>
        <input id="additional-conditions-text" class="input-state" name="other_conditions"
            value="{{ old('other_conditions', $contract->other_conditions) }}" placeholder="اكتب هنا..." />
    </div>
    
    <script>
        function toggleAdditionalConditions() {
            const selectElement = document.getElementById('additional-conditions');
            const additionalConditionsSection = document.getElementById('additional-conditions-section');
    
            if (selectElement.value === 'yes') {
                additionalConditionsSection.style.display = 'flex';
            } else {
                additionalConditionsSection.style.display = 'none';
            }
        }
    
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleAdditionalConditions();
        });
    </script>
    
    <div class="terms-condition">
        <input type="checkbox" id="terms-condition" name="terms_condition"  {{ old('terms_condition') ? 'checked' : '' }} />
        <label for="terms-condition">
            من خلال إنشاء عقد ، فإنك توافق على
            <a href="{{url('terms')}}">
                <span>الشروط والأحكام</span>
            </a>
            الخاصة بنا
        </label>
    </div>

    <div class="buttons-state">
        <a href="{{ url()->previous() }}" class="back-button-state">عودة</a>
        <button type="submit" class="next-button-state">
            التالي
        </button>
    </div>
</form>

<a class="help-text-state" href="https://wa.me/+966597500014">
    واجهتك مشكلة ؟كلمنا على واتساب
    <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
</a>

        <!-- Success Delete Modal -->
    <div class="overlay" id="overlay"></div>
    <div class="success-delete-modal-success" id="successDeleteModal">
      <i class="fa-solid fa-xmark close-icon-success" id="closeModalIcon"></i>
      <img src="{{ asset('website/asset/images/success-modal-icon.svg') }}" alt="" />
      <h2>التوضيحات</h2>
      <button id="closeModalButton">فهمت</button>
    </div>

<script>
    document.getElementById("additional-conditions").addEventListener("change", function() {
        const additionalConditionsTextarea = document.querySelector(".additional-conditions-textarea");
        additionalConditionsTextarea.style.display = this.value === "yes" ? "flex" : "none";
    });

    // Function to save form data to session storage
    function saveFormData() {
        const form = document.getElementById("fund-form");
        const formData = new FormData(form);

        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        sessionStorage.setItem("formData", JSON.stringify(data));
    }

    // Function to load form data from session storage
    function loadFormData() {
        const data = JSON.parse(sessionStorage.getItem("formData"));

        if (data) {
            Object.keys(data).forEach((key) => {
                const element = document.querySelector(`[name="${key}"]`);

                if (element) {
                    if (element.type === "checkbox") {
                        element.checked = data[key] === "on";
                    } else if (element.tagName === "SELECT") {
                        element.value = data[key];
                    } else {
                        element.value = data[key];
                    }
                }
            });

            // Show additional conditions textarea if applicable
            if (data["additional_conditions"] === "yes") {
                document.querySelector(".additional-conditions-textarea").style.display = "flex";
            }
        }
    }
    
        // Load form data when the page is loaded
    window.addEventListener("load", loadFormData);
    
                // Code To Show the Clarify Modal
        document
          .getElementById("clarify")
          .addEventListener("click", function () {
            document.getElementById("overlay").style.display = "block";
            document.getElementById("successDeleteModal").style.display =
              "block";
          });

        document
          .getElementById("closeModalIcon")
          .addEventListener("click", closeModal);
        document
          .getElementById("closeModalButton")
          .addEventListener("click", closeModal);

        function closeModal() {
          document.getElementById("overlay").style.display = "none";
          document.getElementById("successDeleteModal").style.display = "none";
        }

    // Add event listener for form submission
    // document.getElementById("fund-form").addEventListener("submit", function(event) {
    //     saveFormData();
    //     // Uncomment the following line if you want to redirect or handle form submission
    //     // window.location.href = "beforewestart.html";
    // });

    // Load form data when the page is loaded
    window.addEventListener("load", loadFormData);
</script>

@endsection
