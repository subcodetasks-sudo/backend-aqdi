@extends('website.Contract.layout.app')

@section('title', 'الخطوه الرابعه')

@section('content')


    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">معلومات المستأجر</h1>
    <p class="description-state">قم بتعبئة بياناتك بشكل صحيح</p>


    <form method="POST" action="{{ route('real.submit.step4', ['uuid' => $contract->uuid, 'real_id' => $real->id, 'id' => $unit->id]) }}" class="form-content-state" id="rent-form">
      @csrf
      <div class="form-group-state">
        <label for="tenant-type-state" class="label-state">
            صفة المستأجر / فرد - مؤسسة - شركة :
        </label>
        <select id="tenant-type-state" class="select-state" name="tenant_entity">
            <option value="" disabled {{ old('tenant_entity') === null ? 'selected' : '' }}>صفة المستأجر</option>
            <option value="person" {{ old('tenant_entity') == 'person' ? 'selected' : '' }}>فرد</option>
            <option value="institution" {{ old('tenant_entity') == 'institution' ? 'selected' : '' }}>مؤسسة / شركة</option>
        </select>
    </div>
    @error('tenant_entity')
    <div class="error-message">{{ $message }}</div>
@enderror
    <div class="row-state tenant-dependent" style="display: none">
        <div class="form-group-state">
            <label for="tenant-id-number" class="label-state">رقم هوية المستأجر</label>
            <input id="tenant-id-number" class="input-state owner-number" type="text" name="tenant_id_num"
                value="{{ old('tenant_id_num',$contract->tenant_id_num) }}" placeholder="رقم هوية المستأجر" />
        
                @error('tenant_id_num')
                <div class="error-message">{{ $message }}</div>
            @enderror
            </div>
     
        <div class="form-group-state">
            <label for="tenant-birthdate" class="label-state">تاريخ ميلاد المستأجر</label>
            <input id="tenant-birthdate" class="input-state date-picker" type="text" name="tenant_dob"
                value="{{ old('tenant_dob',$contract->tenant_dob) }}" placeholder="تاريخ ميلاد المستأجر" />
        </div>
    
    </div>
    @error('tenant_dob')
        <div class="error-message">{{ $message }}</div>
    @enderror
    <div class="form-group-state tenant-dependent" style="display: none">
        <label for="tenant-phone" class="label-state">رقم جوال المستأجر 05xxxxxxxx</label>
        <input id="tenant-phone" class="input-state phone" type="text" name="tenant_mobile"
            value="{{ old('tenant_mobile',$contract->tenant_mobile) }}" placeholder="رقم جوال المستأجر 05xxxxxxxx" />
    </div>
    @error('tenant_mobile')
    <div class="error-message">{{ $message }}</div>
@enderror
    <div class="form-group-state organization-dependent" style="display: none">
        <label for="tenant-unified-id" class="label-state">رقم السجل الموحد - المبتدئ برقم 7</label>
        <input id="tenant-unified-id" class="input-state" type="number" name="tenant_entity_unified_registry_number"
            value="{{ old('tenant_entity_unified_registry_number',$contract->tenant_entity_unified_registry_number) }}" placeholder="رقم السجل الموحد - المبتدئ برقم 7" />
    </div>
    @error('tenant_entity_unified_registry_number')
    <div class="error-message">{{ $message }}</div>
@enderror
    <div class="row-state organization-dependent" style="display: none">
        <div class="form-group-state">
            <label for="business-location" class="label-state">عنوان النشاط التجاري</label>
            <select id="region_of_the_tenant_legal_agent" class="select-state" name="region_of_the_tenant_legal_agent">
                <option value="none" disabled @if(old('region_of_the_tenant_legal_agent', $contract->region_of_the_tenant_legal_agent) === null) selected @endif>
                    {{__('website.step2.region')}}
                </option>
                @foreach ($regions as $region)
                <option value="{{ $region->id }}" {{ old('region_of_the_tenant_legal_agent', $contract->region_of_the_tenant_legal_agent) == $region->id ? 'selected' : '' }}>
                    {{ $region->name_trans }}
                </option>
                @endforeach
            </select>
        </div>
        @error('region_of_the_tenant_legal_agent')
        <div class="error-message">{{ $message }}</div>
    @enderror
        <div class="form-group-state">
            <label for="city" class="label-state">المدينة</label>
            <select id="city" class="select-state" name="city_of_the_tenant_legal_agent">
                <option value="none" disabled selected>اختر المدينة</option>
            </select>
        </div>
    </div>
    @error('city_of_the_tenant_legal_agent')
    <div class="error-message">{{ $message }}</div>
@enderror
    <div class="form-group-state organization-dependent" style="display: none">
        <label for="authorization-type" class="label-state">نوع التفويض</label>
        <select id="authorization-type" class="select-state" name="authorization_type">
            <option  disabled {{ old('authorization_type') === null ? 'selected' : '' }}>اختر نوع التفويض</option>
            <option value="owner_and_representative_of_record" {{ old('authorization_type') == 'owner_and_representative_of_record' ? 'selected' : '' }}>
                أنا مالك السجل وممثله
            </option>
            <option value="agent_for_the_tenant" {{ old('authorization_type',$contract->authorization_type) == 'agent_for_the_tenant' ? 'selected' : '' }}>
                انا وكيل او مفوض عن مالك السجل
            </option>
        </select>
    </div>
    @error('authorization_type')
    <div class="error-message">{{ $message }}</div>
@enderror
    <div class="row-state organization-dependent" style="display: none">
        <div class="form-group-state">
            <label for="record-owner-agent-id" class="label-state">رقم هوية وكيل مالك السجل</label>
            <input id="record-owner-agent-id" class="input-state owner-number" type="text"
                name="id_num_of_property_tenant_agent"  placeholder="رقم هوية وكيل مالك السجل" 
                value="{{ old('id_num_of_property_tenant_agent', $contract->id_num_of_property_tenant_agent) }}"/>
              </div>
        @error('id_num_of_property_tenant_agent')
        <div class="error-message">{{ $message }}</div>
    @enderror
        <div class="form-group-state">
            <label for="record-owner-agent-birthdate" class="label-state">تاريخ ميلاد وكيل مالك السجل</label>
            <input id="record-owner-agent-birthdate" class="input-state date-picker" type="text"
                name="dob_of_property_tenant_agent" value="{{ old('dob_of_property_tenant_agent',$contract->dob_of_property_tenant_agent) }}" placeholder="تاريخ ميلاد وكيل مالك السجل" />
        </div>
    </div>
    @error('dob_of_property_tenant_agent')
    <div class="error-message">{{ $message }}</div>
@enderror

    <div class="form-group-state organization-dependent" style="display: none">
        <label for="record-owner-agent-phone" class="label-state">رقم جوال مالك السجل</label>
        <input id="record-owner-agent-phone" class="input-state phone" type="text"
            name="mobile_of_property_tenant_agent" 
            value="{{ old('mobile_of_property_tenant_agent', $contract->mobile_of_property_tenant_agent) }}"
            placeholder="رقم جوال مالك السجل" />
    </div>
    @error('mobile_of_property_tenant_agent')
    <div class="error-message">{{ $message }}</div>
@enderror

    
            <div class="form-group-state authorization-type-dependent mystery" id="commercial-register-image-div"
            style="display: none">
            <label for="commercial-register-image" class="label-state">صورة من السجل التجاري</label>
            <input id="commercial-register-image" class="input-state file-upload" type="file"
                name="copy_of_the_owner_record" accept="image/*" />
            <button type="button" class="download-btn" id="commercial-register-download-btn">
                ارفاق صوره
                <i class="fa-solid fa-download" style="display: none"></i>
            </button>
        </div>
        @error('copy_of_the_owner_record')
        <div class="error-message">{{ $message }}</div>
    @enderror
        <div class="form-group-state authorization-type-dependent mystery" id="authorization-image-div"
            style="display: none">
            <label for="authorization-image" class="label-state">صورة من الوكالة او التفويض</label>

            <input id="authorization-image" class="input-state file-upload" type="file" name="copy_of_the_authorization_or_agency"
                accept="image/*" />
            <button type="button" class="download-btn" id="authorization-download-btn">
                ارفاق صوره
                <i class="fa-solid fa-download" style="display: none"></i>
            </button>
        </div>

        @error('copy_of_the_authorization_or_agency')
        <div class="error-message">{{ $message }}</div>
    @enderror
        <div class="buttons-state">
            <a href="{{ url()->previous() }}" class="back-button-state">عودة</a>

            <button type="submit" class="next-button-state">
                التالي
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </div>
    </form>
    </section>
    <a class="help-text-state" href="https://wa.me/+966597500014">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="/images/whatsapp-icon.svg" alt="" />
    </a>

    <script>
        $(document).ready(function() {
            // Update cities function
            function updateCities() {
                var regionId = $('#region_of_the_tenant_legal_agent').val();
    
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
                                cityDropdown.append('<option value="' + city.id + '">' + city.name_trans + '</option>');
                            });
                        } else {
                            cityDropdown.append('<option value="none" disabled>لا توجد مدن متاحة</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching cities:', error);
                    }
                });
            }
    
            // Event listener for region dropdown change
            $('#region_of_the_tenant_legal_agent').change(function() {
                updateCities();
            });
    
            // If a region is already selected, update cities
            if ($('#region_of_the_tenant_legal_agent').val()) {
                updateCities();
            }
        });
    </script>
    <script>
        $(function() {
            initDatePicker("hijri");
            $("#calendar-switcher").on("change", function() {
                const selectedCalendar = $(this).val();
                initDatePicker(selectedCalendar);
            });
        });

        function initDatePicker(calendarType) {
            $("#tenant-birthdate").hijriDatePicker("destroy");
            $("#record-owner-agent-birthdate").hijriDatePicker("destroy");
            if (calendarType === "hijri") {
                $("#tenant-birthdate").hijriDatePicker({
                    hijri: true,
                    showSwitcher: false,
                });
                $("#record-owner-agent-birthdate").hijriDatePicker({
                    hijri: true,
                    showSwitcher: false,
                });
            } else {
                $("#tenant-birthdate").hijriDatePicker({
                    hijri: false,
                    showSwitcher: false,
                });
                $("#record-owner-agent-birthdate").hijriDatePicker({
                    hijri: false,
                    showSwitcher: false,
                });
            }
        }
    </script>
    
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const authorizationTypeSelect =
          document.getElementById("authorization-type");
        const commercialRegisterImageDiv = document.getElementById(
          "commercial-register-image-div"
        );
        const authorizationImageDiv = document.getElementById(
          "authorization-image-div"
        );
        const tenantTypeSelect = document.getElementById("tenant-type-state");
        const tenantFields = document.querySelectorAll(".tenant-dependent");
        const organizationFields = document.querySelectorAll(
          ".organization-dependent"
        );
        function handleAuthorizationTypeChange() {
          const selectedValue = authorizationTypeSelect.value;

          if (selectedValue === "owner_and_representative_of_record") {
            commercialRegisterImageDiv.style.display = "flex";
            authorizationImageDiv.style.display = "none";
          } else if (selectedValue === "agent_for_the_tenant") {
            commercialRegisterImageDiv.style.display = "flex";
            authorizationImageDiv.style.display = "flex";
          } else {
            commercialRegisterImageDiv.style.display = "none";
            authorizationImageDiv.style.display = "none";
          }

          
        }
        
                
        function handleTenantTypeChange() {
          const tenantType = tenantTypeSelect.value;
          const selectedValue = authorizationTypeSelect.value;

          if (tenantType === "person") {
            tenantFields.forEach((field) => (field.style.display = "flex"));
            organizationFields.forEach((field) => {
              const inputs = field.querySelectorAll("input, select");
                inputs.forEach((input) => {
                    input.value = "";
                });
                authorizationImageDiv.style.display = "none";
                commercialRegisterImageDiv.style.display = "none";
                field.style.display = "none";
            });
            
          } else if (tenantType === "institution") {
            organizationFields.forEach(
              (field) => (field.style.display = "flex")
            );
            
            tenantFields.forEach((field) => {
              const inputs = field.querySelectorAll("input, select");
                inputs.forEach((input) => {
                    input.value = "";
                });
                field.style.display = "none";
            });
          }
          }
     
        function saveFormData() {
          const form = document.getElementById("rent-form");
          const formData = new FormData(form);
          const formObject = {};

          formData.forEach((value, key) => {
            formObject[key] = value;
          });

          localStorage.setItem("rentFormData", JSON.stringify(formObject));
        }

        function populateFormData() {
          const savedData = JSON.parse(localStorage.getItem("rentFormData"));

          if (savedData) {
            Object.keys(savedData).forEach((key) => {
              const input = document.querySelector(`[name="${key}"]`);

              if (input) {
                if (input.type === "file") {
                  return;
                }
                input.value = savedData[key];
                if (input.tagName === "SELECT") {
                  input.querySelector(
                    `option[value="${savedData[key]}"]`
                  ).selected = true;
                }
              }
            });

            handleTenantTypeChange();
            handleAuthorizationTypeChange();
          }
        }

        document.querySelectorAll("input, select").forEach((element) => {
          element.addEventListener("input", saveFormData);
          element.addEventListener("change", saveFormData);
        });

        authorizationTypeSelect.addEventListener(
          "change",
          handleAuthorizationTypeChange
        );
        handleTenantTypeChange();

        tenantTypeSelect.addEventListener("change", handleTenantTypeChange);
        document.querySelectorAll(".file-upload").forEach((input) => {
          const downloadBtn = input.nextElementSibling;
          const icon = downloadBtn.querySelector("i");

          downloadBtn.addEventListener("click", function () {
            if (!input.files.length) {
              input.click();
            } else {
              if (input.files.length > 0) {
                const file = input.files[0];
                if (file) {
                  const url = URL.createObjectURL(file);
                  const a = document.createElement("a");
                  a.href = url;
                  a.download = file.name;
                  document.body.appendChild(a);
                  a.click();
                  URL.revokeObjectURL(url);
                  document.body.removeChild(a);
                }
              }
            }
          });

          input.addEventListener("change", function () {
            if (this.files.length > 0) {
              downloadBtn.textContent = "تحميل";
              icon.style.display = "inline-block";
              downloadBtn.appendChild(icon);
              this.classList.add("has-file");
            } else {
              downloadBtn.textContent = "ارفاق صوره";
              icon.style.display = "none";
              this.classList.remove("has-file");
            }
          });
        });

        // document
        //   .querySelector(".form-content-state")
        //   .addEventListener("submit", function (event) {
        //     event.preventDefault();
        //     saveFormData();
        //     window.location.href = "rentunit.html";
        //   });
        populateFormData();
      });
    </script>

@endsection
