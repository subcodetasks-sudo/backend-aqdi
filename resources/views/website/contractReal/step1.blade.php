@extends('website.Contract.layout.app')

@section('title', 'الخطوه الأولي')

@section('content')

    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">بيانات الصك</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    <form action="{{ route('real.submit.step1', ['uuid' => $contract->uuid, 'real_id' => $real->id, 'id' => $unit->id]) }}" method="POST" class="form-content-state">
        @csrf
        <div class="form-group-state">
            <label for="ownership-state" class="label-state">هل أنت المـالك أو المستاجر؟</label>
            <select id="ownership-state" class="select-state" name="contract_ownership">
                <!-- Default placeholder -->
                <option value="" disabled {{ old('contract_ownership', $real->contract_ownership) === null ? 'selected' : '' }}>
                    {{ $real->contract_ownership =='owner' ? 'مالك' : 'مستأجر' }}
                </option>
                <!-- Owner option -->
                <option value="1" {{ old('contract_ownership', $real->contract_ownership) == '1' ? 'selected' : '' }}>
                    مالك
                </option>
                <!-- Tenant option -->
                <option value="0" {{ old('contract_ownership', $real->contract_ownership) == '0' ? 'selected' : '' }}>
                    مستأجر
                </option>
            </select>
            @error('contract_ownership')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        
        
        

        <div class="form-group-state">
            <label for="alive-state" class="label-state">هل صاحب العقار حي يرزق؟</label>
            <select id="alive-state" class="select-state" name="property_owner_is_deceased">
                <option value="0" {{ old('property_owner_is_deceased', $real->property_owner_is_deceased) == '0' || old('property_owner_is_deceased', $contract->property_owner_is_deceased) === null ? 'selected' : '' }} hidden>هل صاحب العقار حي يرزق</option>
                <option value="1" {{ old('property_owner_is_deceased', $real->property_owner_is_deceased) == '1' ? 'selected' : '' }}>نعم، حي يرزق بفضل الله</option>
                <option value="2" {{ old('property_owner_is_deceased', $real->property_owner_is_deceased) == '2' ? 'selected' : '' }}>لا، متوفي يرحمه الله</option>
            </select>

            @error('property_owner_is_deceased')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state" id="dead-type-group" style="display: none">
            <label for="dead-type" class="label-state">حدد نوع الصك</label>
            <select id="dead-type" class="select-state" name="instrument_type">
                <option value="" disabled selected>حدد نوع الصك</option>
                <option value="old_handwritten" {{ old('instrument_type', $real->instrument_type) == 'old_handwritten' ? 'selected' : '' }}> صك ملكية ورقي (قديم)  </option>
                <option value="electronic" {{ old('instrument_type', $real->instrument_type) == 'electronic' ? 'selected' : '' }}> صك ملكية إلكتروني </option>
                <option value="strong_argument" {{ old('instrument_type', $real->instrument_type) == 'strong_argument' ? 'selected' : '' }}>صك السجل العقاري</option>
            </select>
            @error('instrument_type')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state input-doc" style="display: none">
            <label for="document-number" class="label-state">رقم الصك</label>
            <input id="document-number" class="input-state" type="number" name="instrument_number" placeholder="رقم الصك" value="{{ old('instrument_number', $real->instrument_number) }}" />
            @error('instrument_number')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state input-doc" style="display: none">
            <label for="deed-date" class="label-state">تاريخ الصك</label>
            <input id="deed-date" class="input-state date-picker" type="text" name="instrument_history" value="{{ old('instrument_history', $real->instrument_history) }}" placeholder="تاريخ الصك" />
            @error('instrument_history')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state document-input" style="display: none">
            <label for="document-number-registry" class="label-state">رقم السجل العقاري</label>
            <input id="document-number-registry" class="input-state" type="number" name="real_estate_registry_number" value="{{ old('real_estate_registry_number', $real->real_estate_registry_number) }}" placeholder="رقم السجل العقاري" />
            @error('real_estate_registry_number')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state document-input" style="display: none">
            <label for="document-date" class="label-state">تاريخ التسجيل الاول</label>
            <input id="document-date" class="input-state date-picker" type="text" name="date_first_registration" value="{{ old('date_first_registration', $real->date_first_registration) }}" placeholder="تاريخ التسجيل الاول" />
            @error('date_first_registration')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="row-state" style="display: none">
            <div class="form-group-state">
                <label for="property-type" class="label-state">نوع العقار</label>
                <select id="property-type" class="select-state" name="property_type_id">
                    <option value="" disabled selected>نوع العقار</option>
                    @foreach ($realTypes as $item)
                        <option value="{{ $item->id }}" {{ old('property_type_id', $real->property_type_id) == $item->id ? 'selected' : '' }}>{{ $item->name_trans }}</option>
                    @endforeach
                </select>
                @error('property_type_id')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group-state">
                <label for="property-usage" class="label-state">استخدام العقار</label>
                <select id="property-usage" class="select-state" name="property_usages_id">
                    <option value="" disabled selected>استخدام العقار</option>
                    @foreach ($usages as $item)
                        <option value="{{ $item->id }}" {{ old('property_usages_id', $real->property_usages_id) == $item->id ? 'selected' : '' }}>{{ $item->name_trans }}</option>
                    @endforeach
                </select>
                @error('property_usages_id')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row-state" style="display: none">
            <div class="form-group-state">
                <label for="floors-number" class="label-state">عدد الطوابق في العقار</label>
                <select id="floors-number" class="select-state" name="number_of_floors">
                    <option value="" disabled selected>عدد الطوابق في العقار</option>
                    @for ($i = 1; $i <= 30; $i++)
                        <option value="{{ $i }}" {{ old('number_of_floors', $real->number_of_floors) == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
                @error('number_of_floors')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group-state">
                <label for="units-number" class="label-state">عدد الوحدات الموجودة في العقار</label>
                <select id="units-number" class="select-state" name="number_of_units_in_realestate">
                    <option value="" disabled selected>عدد الوحدات الموجودة في العقار</option>
                    @for ($i = 1; $i <= 30; $i++)
                        <option value="{{ $i }}" {{ old('number_of_units_in_realestate', $real->number_of_units_in_realestate) == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
                @error('number_of_units_in_realestate')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="buttons-state">
            <a type="button" href="{{ url()->previous() }}" class="back-button-state">عودة</a>
            <button type="submit" class="next-button-state">
                التالي
                <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </div>
    </form>
    </section>
    <a class="help-text-state" href="">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
    </a>

    <!-- Modals Part -->
    <!-- Died Modal -->
    <div class="modal-overlay-died" id="died-modal">
        <div class="modal-content-died">
            <div class="modal-header-died">
                <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-died" />
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
                    لانستطيع معالجة صك المتوفي حاليا<br />
                    الرجاء مراسلتنا عبر الواتساب ليقوم فريقنا بخدمتكم
                </p>
                <a href="https://wa.me/{{ urlencode($setting->whatsapp ?? '') }}" class="whatsapp-link-died">
                    ستضاف رسوم بقيمة 150 ريال لمعالجة صك المتوفي
                </a>
                    <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="Arrow"
                        class="arrow-icon-died" />
                </a>
            </div>
        </div>
    </div>

    <!-- Paper Modal -->
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
                <a href="https://wa.me/your-number" class="whatsapp-link-died">
                    ستضاف رسوم بقيمة 55 ريال لمعالجة صك المتوفي
                    <img src="{{ asset('website/asset/images/white-left-arrow.svg')}}" alt="Arrow" class="arrow-icon-died" />
                </a>
            </div>
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
            const aliveSelect = document.getElementById("alive-state");
            const diedModalOverlay = document.querySelector("#died-modal");
            const closeIconDied =
                diedModalOverlay.querySelector(".close-icon-died");
            const paperDeedModal = document.getElementById("paper-deed-modal");
            const closePaperModal = paperDeedModal.querySelector("#close-modal");

            const deadTypeGroup = document.getElementById("dead-type-group");
            const deadTypeSelect = document.getElementById("dead-type");
            const documentFields = document.querySelectorAll(".input-doc");
            const realStateRegistryFields =
                document.querySelectorAll(".document-input");
            const rowStates = document.querySelectorAll(".row-state");
            
            
            
            aliveSelect.value= "1";
            handleVisibility();
            

            function showDiedModal() {
                diedModalOverlay.style.display = "flex";
            }

            function closeDiedModal() {
                diedModalOverlay.style.display = "none";
            }

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
                      const aliveState = aliveSelect.value;
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
                            // if (input) {
                            //     input.value = "";
                            // }
                        });
                        showDocumentFields();
                        hideRealStateRegistryFields();
                      } else if (deadType === "strong_argument") {
                        documentFields.forEach((field) => {
                            const input = field.querySelector('.input-state');
                            // if (input) {
                            //     input.value = "";
                            // }
                        });
                        showRealStateRegistryFields();
                        hideDocumentFields();
                      }
            
                      if (deadType === "electronic" || deadType === "strong_argument") {
                        rowStates.forEach((row) => (row.style.display = "flex"));
                      }
            
                      if (aliveState === "2") {
                        deadTypeSelect.value = "";
                        rowStates.forEach((row) => (row.style.display = "none"));
                        documentFields.forEach((field) => (field.style.display = "none"));
                        realStateRegistryFields.forEach(
                          (field) => (field.style.display = "none")
                        );
                        deadTypeGroup.style.display = "none";
            
                        showDiedModal();
                      } else if (aliveState === "1") {
                        deadTypeGroup.style.display = "flex";
                      }
                    }


            function saveFormData() {
                const deedForm = {
                    "alive-state": document.querySelector("#alive-state").value,
                    "dead-type": document.querySelector("#dead-type").value,
                };
                localStorage.setItem("deedForm", JSON.stringify(deedForm));
            }

            function populateForm() {
                const savedData = JSON.parse(localStorage.getItem("deedForm"));
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
            document
                .querySelector("#alive-state")
                .addEventListener("change", handleVisibility);

            closeIconDied.addEventListener("click", closeDiedModal);

            closePaperModal.addEventListener("click", closePaperDeedModal);

            populateForm();
        });
    </script>

@endsection
