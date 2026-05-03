@extends('website.auth.app')

@section('title', 'الوحدات')

@section('content')
<section class="real-state">
    <a href="{{ url()->previous() }}"> 
        <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" class="back-arrow" />
    </a>
    <div class="real-state-heading">
        <a href="{{ url()->previous() }}" class="slider-arrow" aria-label="Next Slide">
    <img
        src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}"
        alt="Next"
        class="back-arrow"
    />
</a>

          
          <h2>الوحدات</h2>
        <p>تستطيع تعديل واستعراض وإضافة الوحدات</p>
            @if($userReal->contract_type=='housing')

          <p class="alert-text">لقد قمت بأختيار عقد ايجار سكني ستظهر لك الوحداة السكنية فقط</p>
        @else
        <p class="alert-text">لقد قمت بأختيار عقد ايجار تجاري ستظهر لك الوحداة التجارية فقط</p>

        @endif


        <!-- Create New Unit Link (outside of the loop) -->
        <a href="{{ route('create.realUnit', ['id' => $userReal->id]) }}" id="clearUnit" class="edit-unit">
            انشاء وحدة جديدة
            <i class="fa-solid fa-plus"></i>
        </a>
    </div>

    <div class="properties-holder">
        <div class="container-unit slide slide-active">
            @foreach ($units as $unit)
            <!-- Unit -->
            <div class="section-unit">
                <i class="fa-solid fa-xmark x-button delete-icon-unit" ></i>
                <div class="edit-header-unit">
                    <h2>رقم الوحده {{ $unit->unit_number }}</h2>
                    <a href="{{ url('edit/unit', ['id' => $unit->id]) }}"> تعديل الوحده <i class="fa-solid fa-pen"></i></a>
                </div>
                <div class="separator"></div>
                <form action="{{ route('contract.unit.real', ['id' => $unit->id]) }}" method="POST">
                     
                    @csrf
                        <button  class="button-link" >
                      انشاء عقد ايجار على هذي الوحدة
                      <i class="fa-solid fa-arrow-left"></i>
                    </button>
                  </a>
                </form>
                <ul class="info-list-unit">
                    <li><i class="fa-solid fa-circle-check"></i> نوع الوحدة: {{ $unit->unitType ? $unit->unitType->name_ar : 'غير محدد' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> استخدام الوحدة: {{ $unit->unitUsage ? $unit->unitUsage->name_ar : 'غير محدد' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> رقم الطابق: {{ $unit->floor_number ?? 'غير متوفر' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> مساحة الوحدة: {{ $unit->unit_area ?? 'غير متوفر' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> عدد مكيفات (شباك): {{ $unit->window_ac ?? 'غير متوفر' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> عدد مكيفات (سبليت): {{ $unit->split_ac ?? 'غير متوفر' }}</li>
                    @if($userReal->contract_type=='housing')
                    <li><i class="fa-solid fa-circle-check"></i> عدد دورات المياه: {{ $unit->The_number_of_toilets ?? 'غير محدد' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> رقم عداد الكهرباء (ان وجد): {{ $unit->electricity_meter_number ?? 'غير متوفر' }}</li>
                    <li><i class="fa-solid fa-circle-check"></i> رقم عداد المياه (ان وجد): {{ $unit->water_meter_number ?? 'غير متوفر' }}</li>
                    @endif
                    @if($userReal->contract_type=='commercial')

                    <li><i class="fa-solid fa-circle-check"></i>   التأجير من الباطن: 
                        @if($unit->sub_delay==1)
                            نعم
                        @else
                            غير متوفر
                        @endif
                    </li>
                    @endif
                    </li>
                    
                    <li><i class="fa-solid fa-circle-check"></i> اضافه بتاريخ: {{ $unit->created_at ? $unit->created_at->format('Y-m-d H:i:s') : 'غير متوفر' }}</li>
                </ul>
            </div>
            @endforeach
        </div> 
    </div>

<!--Modal create a new contract unit--->
        <!-- Start Modal Part -->
        <form action="" method="POST">
          @csrf
        <div class="modal-overlay-choose">
          <div class="modal-content-choose">
            <div class="modal-header-choose">
              <img
                src="{{asset('website/asset/images/navy-x-icon.svg')}}"
                alt="Close"
                class="close-icon-choose"
              />
            </div>

            <!-- Modal Body -->
            <div class="modal-body-choose">
              <img src="{{asset('website/asset/images/choosecontracttype-icon.svg')}}" alt="" />
              <h1 class="modal-title-choose">اختر نوع عقد الايجار</h1>
              <p class="modal-text-choose">
                اختر العقد الذي ترغب به ( سكني - تجاري )
              </p>
              <div class="options-boxes-choose">
                <div class="holder-choose">
                  <div class="option-box-choose active">
                    <img src="{{asset('website/assetimages/solid-commerical-contract.svg')}}" alt="" />
                    <h2>عقد ايجار سـكني</h2>
                    <p>عمارة - شقة - فيلا - غرفة</p>

                    <img
                      class="selected-icon-choose"
                      src="{{asset('website/assetimages/solid-empty-circle.svg')}}"
                      alt=""
                    />
                  </div>
                  <div class="bottom-text-choose">
                    <p>قيمة العقد السكني</p>
                    <img src="{{asset('website/assetimages/small-grey-left-arrow.svg')}}" alt="" />
                  </div>
                </div>

                <div class="holder-choose">
                  <div class="option-box-choose">
                    <img src="{{asset('website/assetimages/solid-commerical-contract.svg')}}" alt="" />
                    <h2>عقد ايجار تجــاري</h2>
                    <p>محل - سوق - فندق - مصنع</p>

                    <img
                      class="selected-icon-choose"
                      src="{{asset('website/assetimages/solid-empty-circle.svg')}}"
                      alt=""
                    />
                  </div>
                  <div class="bottom-text-choose">
                    <p>قيمة العقد التجاري</p>
                    <img src="{{asset('website/assetimages/small-grey-left-arrow.svg')}}" alt="" />
                  </div>
                </div>
              </div>
              <div class="contract-modal-footer-choose">
                <a href="" class="back-choose">عوده</a>
                <a href="contractforms/beforewestart.html" class="next-choose"
                  >متابعه
                  <i class="fa-solid fa-arrow-left-long"></i>
                
                </a>
              </div>
            </div>
          </form>

          </div>
        </div>

     <!--End Modal create a new contract unit--->
      <!-- Modals Section -->
      <div class="overlay" id="delete-modal-overlay"></div>
      <div class="delete-modal-delete" id="delete-modal-delete">
        <i class="fa-solid fa-xmark close-icon-delete"></i>
        <img
          src="{{ asset('website/asset/images/recycle-icon.svg') }}"
          alt=""
          class="recycle-icon-delete"
        />
        <h2>تاكيد الحذف</h2>
        <p>هل انت متاكد من حذف الوحده؟</p>
        <div class="button-container-delete">
          <button class="cancel-delete">عودة</button>
       
        @foreach($units as $unit)
  <form action="{{ route('unit.delete', [$unit->id]) }}" method="POST">
    @csrf
    <button class="confirm-delete" type="submit">تأكيد</button>
  </form>
@endforeach

        
        
        
        </div>
      </div>

      <!-- Success Delete Modal -->
      <div class="overlay-success"></div>
      <div class="success-delete-modal-success">
        <i class="fa-solid fa-xmark close-icon-success"></i>
        <img src="{{ asset('website/asset/images/success-modal-icon.svg') }}" alt="" />
        <h2>تم حذف الوحده بنجاح</h2>
        <a href="auth-home.html" class="confirm-success">العودة للرئيسية</a>
      </div>  
      
          <div class="pagination">
      <ul class="page-list">
        <li class="page-item"><i class="fa-solid fa-arrow-right"></i></li>
        <li class="page-item active">1</li>
        <li class="page-item">2</li>
        <li class="page-item">3</li>
        <li class="page-item">4</li>
        <li class="page-item">5</li>
        <li class="page-item"><i class="fa-solid fa-arrow-left"></i></li>
      </ul>
    </div>

</section>

<script>

document.getElementById('clearUnit').onclick = () => localStorage.clear();

          // Modal logic for delete functionality
    (function () {
      const deleteIcons = document.querySelectorAll(".delete-icon-unit");
      const overlay = document.getElementById("delete-modal-overlay");
      const modal = document.getElementById("delete-modal-delete");
      const closeIcon = document.querySelector(".close-icon-delete");
      const cancelDelete = document.querySelector(".cancel-delete");
      const confirmDelete = document.querySelector(".confirm-delete");

      const successOverlay = document.querySelector(".overlay-success");
      const successModal = document.querySelector(".success-delete-modal-success");
      const closeSuccessIcon = document.querySelector(".close-icon-success");

      function showModal() {
        overlay.style.display = "block";
        modal.style.display = "block";
      }

      function hideModal() {
        overlay.style.display = "none";
        modal.style.display = "none";
      }

      function showSuccessModal() {
        hideModal();
        successOverlay.style.display = "block";
        successModal.style.display = "block";
       }

      function hideSuccessModal() {
        successOverlay.style.display = "none";
        successModal.style.display = "none";
      }

      // Apply the delete functionality to all delete icons
      deleteIcons.forEach((deleteIcon) => {
        deleteIcon.addEventListener("click", showModal);
      });

      if (closeIcon) {
        closeIcon.addEventListener("click", hideModal);
      }
      if (cancelDelete) {
        cancelDelete.addEventListener("click", hideModal);
      }
      if (overlay) {
        overlay.addEventListener("click", hideModal);
      }
      if (confirmDelete) {
        confirmDelete.addEventListener("click", showSuccessModal);
      }
      if (closeSuccessIcon) {
        closeSuccessIcon.addEventListener("click", hideSuccessModal);
      }
      if (successOverlay) {
        successOverlay.addEventListener("click", hideSuccessModal);
      }
    })();


// create contract unit 

// Modal logic for choosing contract type functionality
(function () {
        const modalOverlay = document.querySelector(".modal-overlay-choose");
        const closeIcon = document.querySelector(".close-icon-choose");
        const optionBoxes = document.querySelectorAll(".option-box-choose");
        const showModalButton = document.getElementById("button-show-modal");

        function showModal() {
          modalOverlay.style.display = "flex";
        }

        function closeModal() {
          modalOverlay.style.display = "none";
        }

        if (showModalButton) {
          showModalButton.addEventListener("click", showModal);
        }

        if (closeIcon) {
          closeIcon.addEventListener("click", closeModal);
        }

        if (modalOverlay) {
          modalOverlay.addEventListener("click", function (event) {
            const modalContent = document.querySelector(
              ".modal-content-choose"
            );
            if (!modalContent.contains(event.target)) {
              closeModal();
            }
          });
        }

        if (optionBoxes) {
          optionBoxes.forEach((box) => {
            box.addEventListener("click", function () {
              optionBoxes.forEach((box) => {
                box.classList.remove("active");
                const selectedIcon = box.querySelector(".selected-icon-choose");
                if (selectedIcon) {
                  selectedIcon.src = "{{ asset('website/asset/images/solid-empty-circle.svg') }}";
                  selectedIcon.style.backgroundColor = "transparent";
                  selectedIcon.style.width = "unset";
                  selectedIcon.style.height = "unset";
                  selectedIcon.style.padding = "unset";
                }
              });

              this.classList.add("active");
              const selectedIcon = this.querySelector(".selected-icon-choose");
              if (selectedIcon) {
                selectedIcon.src = "{{ asset('website/asset/images/selected-icon.svg') }}";
                selectedIcon.style.backgroundColor = "#319e90";
                selectedIcon.style.width = "16px";
                selectedIcon.style.height = "16px";
                selectedIcon.style.padding = "5px";
              }
            });
          });
        }
      })();


</script>



@endsection


