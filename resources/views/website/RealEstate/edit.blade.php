@extends('website.RealEstate.app')

@section('title', 'الخطوة الأولى')

@section('content')
    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">بيانات الصك</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>
    <form class="form-content-state" method="POST" action="{{ route('create.step1.realEstate') }}">
        @csrf

        <div class="form-group-state">
            <label for="ownership-state" class="label-state">هل أنت المـالك أو المستاجر؟</label>
            <select id="ownership-state" class="select-state" name="contract_ownership">
                <option value="" disabled selected>هل أنت المـالك أو المستاجر؟</option>
                <option value="1" {{ old('contract_ownership', $realEstate->contract_ownership) == '1' ? 'selected' : '' }}>مالك</option>
                <option value="0" {{ old('contract_ownership', $realEstate->contract_ownership) == '0' ? 'selected' : '' }}>مستأجر</option>
            </select>
            @error('contract_ownership')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group-state">
            <label for="alive-state" class="label-state">هل صاحب العقار حي يرزق؟</label>
            <select id="alive-state" class="select-state" name="property_owner_is_deceased">
                <option value="" disabled {{ old('property_owner_is_deceased', $realEstate->property_owner_is_deceased) === null ? 'selected' : '' }}>هل صاحب العقار حي يرزق؟</option>
                <option value="1" {{ old('property_owner_is_deceased', $realEstate->property_owner_is_deceased) == '1' ? 'selected' : '' }}>نعم، حي يرزق بفضل الله</option>
                <option value="0" {{ old('property_owner_is_deceased', $realEstate->property_owner_is_deceased) == '0' ? 'selected' : '' }}>لا، متوفي يرحمه الله</option>
            </select>
        </div>

        <div class="form-group-state" id="dead-type-group" style="display: none">
            <label for="dead-type" class="label-state">حدد نوع الصك</label>
            <select id="dead-type" class="select-state" name="instrument_type">
                <option value="" disabled selected>حدد نوع الصك</option>
                <option value="old_handwritten" {{ old('instrument_type') == 'old_handwritten' ? 'selected' : '' }}>صك ورقي</option>
                <option value="electronic" {{ old('instrument_type') == 'electronic' ? 'selected' : '' }}>صك الكتروني</option>
                <option value="strong_argument" {{ old('instrument_type') == 'strong_argument' ? 'selected' : '' }}>صك السجل العقاري</option>
            </select>
        </div>

        <div class="form-group-state input-doc" style="display: none">
            <label for="document-number" class="label-state">رقم الصك</label>
            <input id="document-number" class="input-state" type="number" name="instrument_number" placeholder="رقم الصك" value="{{ old('instrument_number') }}" />
        </div>

        <div class="form-group-state input-doc" style="display: none">
            <label for="deed-date" class="label-state">تاريخ الصك</label>
            <input id="deed-date" class="input-state date-picker" type="text" name="instrument_history" value="{{ old('instrument_history') }}" placeholder="تاريخ الصك" />
        </div>

        <div class="form-group-state document-input" style="display: none">
            <label for="document-number-registry" class="label-state">رقم السجل العقاري</label>
            <input id="document-number-registry" class="input-state" type="number" name="real_estate_registry_number" value="{{ old('real_estate_registry_number') }}" placeholder="رقم السجل العقاري" />
        </div>

        <div class="form-group-state document-input" style="display: none">
            <label for="document-date" class="label-state">تاريخ التسجيل الاول</label>
            <input id="document-date" class="input-state date-picker" type="text" name="date_first_registration" value="{{ old('date_first_registration') }}" placeholder="تاريخ التسجيل الاول" />
        </div>

        <div class="row-state" style="display: none">
            <div class="form-group-state">
                <label for="property-type" class="label-state">نوع العقار</label>
                <select id="property-type" class="select-state" name="property_type_id">
                    <option value="" disabled selected>نوع العقار</option>
                    @foreach ($realTypes as $item)
                        <option value="{{ $item->id }}" {{ old('property_type_id', $realEstate->property_type_id) == $item->id ? 'selected' : '' }}>{{ $item->name_trans }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group-state">
                <label for="property-usage" class="label-state">استخدام العقار</label>
                <select id="property-usage" class="select-state" name="property_usages_id">
                    <option value="" disabled selected>استخدام العقار</option>
                    @foreach ($usages as $item)
                        <option value="{{ $item->id }}" {{ old('property_usages_id', $realEstate->property_usages_id) == $item->id ? 'selected' : '' }}>{{ $item->name_trans }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row-state" style="display: none">
            <div class="form-group-state">
                <label for="floors-number" class="label-state">عدد الطوابق في العقار</label>
                <select id="floors-number" class="select-state" name="number_of_floors">
                    <option value="" disabled selected>عدد الطوابق في العقار</option>
                    @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ old('number_of_floors') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group-state">
                <label for="units-number" class="label-state">عدد الوحدات الموجودة في العقار</label>
                <select id="units-number" class="select-state" name="number_of_units_in_realestate">
                    <option value="" disabled selected>عدد الوحدات الموجودة في العقار</option>
                    @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ old('number_of_units_in_realestate') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="buttons-state">
            <a type="button" href="{{ route('realEstate') }}" class="back-button-state">عودة</a>
            <button type="submit" class="next-button-state">
                التالي
                <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </div>
    </form>
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
            <div class="modal-body-died">
                <div class="modal-images-died">
                    <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="Image 1" class="modal-image-died" />
                    <div class="separator-died"></div>
                    <img src="{{ asset('website/asset/images/logo.png') }}" alt="Image 2" class="modal-image-died" />
                </div>
                <h1 class="modal-title-died">عفوا</h1>
                <p class="modal-text-died">
                    في حال كان صاحب العقار متوفي، يتعين عليك اولاً عمل توكيل لواحد من الورثة او توكيل لأحد المقيمين على العقار.
                    ثم بعد ذلك يمكننا اكمالك إجراءات تسجيل العقار في الخدمة.
                </p>
                <div class="modal-buttons-died">
                    <a href="{{ route('realEstate') }}" class="modal-btn-died">متابعة</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Javascript -->
     
    <script>
        $(function () {
          initDatePicker("hijri");
          $("#calendar-switcher").on("change", function () {
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
        $(function () {
          initDatePicker("hijri");
          $("#calendar-switcher").on("change", function () {
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
        document.addEventListener("DOMContentLoaded", function () {
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
  
            if (aliveState === "0") {
              deadTypeGroup.style.display = "none";
              showDiedModal();
            } else if (aliveState === "1") {
              deadTypeGroup.style.display = "flex";
            }
  
            if (deadType === "old_handwritten") {
              showPaperDeedModal();
            } else if (deadType === "electronic") {
              showDocumentFields();
              hideRealStateRegistryFields();
            } else if (deadType === "strong_argument") {
              showRealStateRegistryFields();
              hideDocumentFields();
            }
  
            if (deadType === "electronic" || deadType === "strong_argument") {
              rowStates.forEach((row) => (row.style.display = "flex"));
            }
          }
  
          function saveFormData() {
            const addingstateForm = {
              "ownership-state": document.querySelector("#ownership-state").value,
              "alive-state": document.querySelector("#alive-state").value,
              "dead-type": document.querySelector("#dead-type").value,
              "document-number": document.querySelector("#document-number").value,
              "document-number-registry": document.querySelector(
                "#document-number-registry"
              ).value,
              "deed-date": document.querySelector("#deed-date").value,
              "document-date": document.querySelector("#document-date").value,
              "property-type": document.querySelector("#property-type").value,
              "property-usage": document.querySelector("#property-usage").value,
              "floors-number": document.querySelector("#floors-number").value,
              "units-number": document.querySelector("#units-number").value,
            };
            localStorage.setItem("addingstateForm", JSON.stringify(addingstateForm));
          }
  
          function populateForm() {
            const savedData = JSON.parse(localStorage.getItem("addingstateForm"));
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
          diedModalOverlay.addEventListener("click", function (event) {
            const modalContent = diedModalOverlay.querySelector(
              ".modal-content-died"
            );
            if (!modalContent.contains(event.target)) {
              closeDiedModal();
            }
          });
  
          closePaperModal.addEventListener("click", closePaperDeedModal);
          paperDeedModal.addEventListener("click", function (event) {
            const modalContent = paperDeedModal.querySelector(
              ".modal-content-died"
            );
            if (!modalContent.contains(event.target)) {
              closePaperDeedModal();
            }
          });
  
          populateForm();
        });
      </script>
    
@endsection
