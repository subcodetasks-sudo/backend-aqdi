@extends('website.RealEstate.app')

@section('title', 'بيانات الصك')

 <script
      src="https://kit.fontawesome.com/5ef60b71ad.js"
      crossorigin="anonymous"
    ></script>

@section('content')
    <section>
        <h3 class="heading3-state">رحلتك الإيجارية أصبحت أسهل</h3>
        <h1 class="heading1-state">بيانات الصك</h1>
        <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
        <form class="form-content-state" method="POST" action="{{ route('realestate.step1.update', $realEstate->id) }}">
            @csrf <!-- Add CSRF token for security -->
            <div class="form-group-state" id="property-name-text">
                <label for="property-name" class="label-state">اسم العقار</label>
                <input id="property-name" class="input-state" type="text" name="name_real_estate"
                    placeholder="اسم العقار" value="{{ old('name_real_estate', $realEstate->name_real_estate) }}" />
            </div>
            <div class="form-group-state" id="dead-type-group">
                <label for="dead-type" class="label-state">حدد نوع الصك</label>
                <select id="dead-type" class="select-state" name="instrument_type">
                    <option value="" disabled
                        {{ old('instrument_type', $realEstate->instrument_type) === null ? 'selected' : '' }}>
                        حدد نوع الصك
                    </option>
                    <option value="old_handwritten"
                        {{ old('instrument_type', $realEstate->instrument_type) === 'old_handwritten' ? 'selected' : '' }}>
                       صك ملكية ورقي (قديم)

                    </option>
                    <option value="electronic"
                        {{ old('instrument_type', $realEstate->instrument_type) === 'electronic' ? 'selected' : '' }}>
                      صك ملكية إلكتروني 

                    </option>
                    <option value="strong_argument"
                        {{ old('instrument_type', $realEstate->instrument_type) === 'strong_argument' ? 'selected' : '' }}>
                        صك السجل العقاري
                    </option>
                </select>
            </div>

            <!-- Conditional Fields for Document -->
            <div class="form-group-state input-doc"
                style="{{ $realEstate->dead_type === 'old_handwritten' || $realEstate->dead_type === 'electronic' ? 'display:block;' : 'display:none;' }}">
                <label for="document-number" class="label-state">رقم الصك</label>
                <input id="document-number" class="input-state" type="number" name="instrument_number"
                    placeholder="رقم الصك" value="{{ old('instrument_number', $realEstate->instrument_number) }}" />
            </div>

            <div class="form-group-state input-doc"
                style="{{ $realEstate->dead_type === 'old_handwritten' || $realEstate->dead_type === 'electronic' ? 'display:block;' : 'display:none;' }}">
                <label for="deed-date" class="label-state">تاريخ الصك</label>
                <input id="deed-date" class="input-state date-picker" type="text" name="instrument_history"
                    placeholder="تاريخ الصك" value="{{ old('instrument_history', $realEstate->instrument_history) }}" />
            </div>

            <!-- Conditional Fields for Real Estate Registry -->
            <div class="form-group-state document-input"
                style="{{ $realEstate->dead_type === 'strong_argument' ? 'display:block;' : 'display:none;' }}">
                <label for="document-number-registry" class="label-state">رقم السجل العقاري</label>
                <input id="document-number-registry" class="input-state" type="number" name="real_estate_registry_number"
                    placeholder="رقم السجل العقاري"
                    value="{{ old('real_estate_registry_number', $realEstate->real_estate_registry_number) }}" />
            </div>

            <div class="form-group-state document-input"
                style="{{ $realEstate->dead_type === 'strong_argument' ? 'display:block;' : 'display:none;' }}">
                <label for="document-date" class="label-state">تاريخ التسجيل الأول</label>
                <input id="document-date" class="input-state date-picker" type="text" name="date_first_registration"
                    placeholder="تاريخ التسجيل الأول"
                    value="{{ old('date_first_registration', $realEstate->date_first_registration) }}" />
            </div>

            <!-- Property and Usage Selection -->
            <div class="row-state">
                <div class="form-group-state">
                    <label for="property-type" class="label-state">نوع العقار</label>
                    <select id="property-type" class="select-state" name="property_type_id">
                        <option value="" disabled
                            {{ old('property_type_id', $realEstate->property_type_id) === null ? 'selected' : '' }}>نوع
                            العقار</option>
                        @foreach ($realTypes as $item)
                            <option value="{{ $item->id }}"
                                {{ old('property_type_id', $realEstate->property_type_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name_trans }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group-state">
                    <label for="property-usage" class="label-state">استخدام العقار</label>
                    <select id="property-usage" class="select-state" name="property_usages_id">
                        <option value="" disabled
                            {{ old('property_usages_id', $realEstate->property_usages_id) === null ? 'selected' : '' }}>
                            استخدام العقار</option>
                        @foreach ($usages as $item)
                            <option value="{{ $item->id }}"
                                {{ old('property_usages_id', $realEstate->property_usages_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name_trans }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Floors and Units -->
            <div class="row-state">
                <div class="form-group-state">
                    <label for="floors-number" class="label-state">عدد الطوابق في العقار</label>
                    <select id="floors-number" class="select-state" name="number_of_floors">
                        <option value="" disabled
                            {{ old('number_of_floors', $realEstate->number_of_floors) === null ? 'selected' : '' }}>عدد
                            الطوابق</option>
                        <option value="1"
                            {{ old('number_of_floors', $realEstate->number_of_floors) == '1' ? 'selected' : '' }}>1
                        </option>
                        <option value="2"
                            {{ old('number_of_floors', $realEstate->number_of_floors) == '2' ? 'selected' : '' }}>2
                        </option>
                        <!-- Add more options if needed -->
                    </select>
                </div>
                <div class="form-group-state">
                    <label for="units-number" class="label-state">عدد الوحدات الموجودة في العقار</label>
                    <select id="units-number" class="select-state" name="number_of_units_in_realestate">
                        <option value="" disabled
                            {{ old('number_of_units_in_realestate', $realEstate->number_of_units_in_realestate) === null ? 'selected' : '' }}>عدد الوحدات
                        </option>
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}"
                                {{ old('number_of_units_in_realestate', $realEstate->number_of_units_in_realestate) == $i ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="buttons-state">
                <a type="button" href="{{ url()->previous() }}" class="back-button-state">عودة</a>
                <button type="submit" class="next-button-state">
                    حفظ
                    <i class="fa-solid fa-arrow-left-long"></i>
                </button>
            </div>
        </form>
    </section>
    <a class="help-text-state" href="https://wa.me/your-number">
        واجهتك مشكلة؟ كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp Icon" />
    </a>

    <!-- Modal Part -->
    <div class="modal-overlay-died" id="paper-deed-modal">
        <div class="modal-content-died">
            <div class="modal-header-died">
                <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-died"
                    id="close-modal" />
            </div>
            <!-- Modal Body -->
            <div class="modal-body-died">
                <div class="modal-images-died">
                    <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="Image 1"
                        class="modal-image-died" />
                    <div class="separator-died"></div>
                    <img src="{{ asset('website/asset/images/logo.png') }}" alt="Image 2" class="modal-image-died" />
                </div>
                <h1 class="modal-title-died">عفوا</h1>
                 <p class="modal-text-died">
                    لانستطيع معالجة صك الورقي حاليا<br />
                    الرجاء مراسلتنا عبر الواتساب ليقوم فريقنا بخدمتكم
                </p>
            </div>
            <a href="https://wa.me/your-number" class="whatsapp-link-died">
            ستضاف رسوم بقيمة 55 ريال لمعالجة صك المتوفي
            <i class="fa-solid fa-arrow-left"></i>
          </a>
        </div>
    </div>
    
    


    <script>
        $(function() {
            initDatePicker("hijri");
            $("#calendar-switcher").on("change", function() {
                const selectedCalendar = $(this).val();
                initDatePicker(selectedCalendar);
            });
        });

        function initDatePicker(calendarType) {
            $("#document-date").hijriDatePicker("destroy");
            $("#deed-date").hijriDatePicker("destroy");
            if (calendarType === "hijri") {
                $("#document-date").hijriDatePicker({
                    hijri: true,
                    showSwitcher: false,
                });
                $("#deed-date").hijriDatePicker({
                    hijri: true,
                    showSwitcher: false,
                });
            } else {
                $("#document-date").hijriDatePicker({
                    hijri: false,
                    showSwitcher: false,
                });
                $("#deed-date").hijriDatePicker({
                    hijri: false,
                    showSwitcher: false,
                });
            }
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector(".form-content-state");
            const paperDeedModal = document.getElementById("paper-deed-modal");
            const closePaperModal = paperDeedModal.querySelector("#close-modal");

            const deadTypeGroup = document.getElementById("dead-type-group");
            const deadTypeSelect = document.getElementById("dead-type");
            const documentFields = document.querySelectorAll(".input-doc");
            const realStateRegistryFields =
                document.querySelectorAll(".document-input");
            const rowStates = document.querySelectorAll(".row-state");
            
            handleVisibility();

            function showPaperDeedModal() {
                paperDeedModal.style.display = "flex";
            }

            function closePaperDeedModal() {
                paperDeedModal.style.display = "none";
            }

            function showDocumentFields() {
                documentFields.forEach((field) => (field.style.display = "flex"));
            }

            function hideDocumentFields() {
                documentFields.forEach((field) => (field.style.display = "none"));
            }

            function showRealStateRegistryFields() {
                realStateRegistryFields.forEach(
                    (field) => (field.style.display = "flex")
                );
            }

            function hideRealStateRegistryFields() {
                realStateRegistryFields.forEach(
                    (field) => (field.style.display = "none")
                );
            }

          function handleVisibility() {
            const deadType = deadTypeSelect.value;
        
            if (deadType === "old_handwritten") {
                rowStates.forEach((row) => (row.style.display = "none"));
                documentFields.forEach((field) => (field.style.display = "none"));
                realStateRegistryFields.forEach(
                    (field) => (field.style.display = "none")
                );
                showPaperDeedModal();
            } else if (deadType === "electronic") {
                realStateRegistryFields.forEach((field) => {
                    const input = field.querySelector('.input-state');
                    if (input) {
                        input.value = "";
                    }
                });
                showDocumentFields();
                hideRealStateRegistryFields();
            } else if (deadType === "strong_argument") {
                documentFields.forEach((field) => {
                    const input = field.querySelector('.input-state');
                    if (input) {
                        input.value = "";
                    }
                });
                showRealStateRegistryFields();
                hideDocumentFields();
            }
        
            if (deadType === "electronic" || deadType === "strong_argument") {
                rowStates.forEach((row) => (row.style.display = "flex"));
            }
        }


            function saveFormData() {
                const addingstateForm = {
                    "dead-type": document.querySelector("#dead-type").value,
                    "property-name": document.querySelector("#property-name").value,
                };
                localStorage.setItem(
                    "addingstateForm-edit",
                    JSON.stringify(addingstateForm)
                );
            }

            function populateForm() {
                const savedData = JSON.parse(localStorage.getItem("addingstateForm-edit"));
                if (savedData) {
                    for (const key in savedData) {
                        if (savedData.hasOwnProperty(key)) {
                            const element = document.querySelector(`#${key}`);
                            if (element) {
                                element.value = savedData[key];
                            }
                        }
                    }
                    handleVisibility();
                }
            }

            document.querySelectorAll("input, select").forEach((element) => {
                element.addEventListener("change", saveFormData);
            });

            document
                .querySelector("#dead-type")
                .addEventListener("change", handleVisibility);

            closePaperModal.addEventListener("click", closePaperDeedModal);
            populateForm();
        });
    </script>
@endsection
