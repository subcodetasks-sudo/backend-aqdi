@extends('website.auth.app')

@section('title', 'عقاراتي')

@section('content')



<section class="real-state">
    <div class="real-state-heading">
        <a href="#" class="your-class-here">
            <img src="{{ asset('website/asset/images/property-icon.svg') }}" alt="Property Icon" />
        </a>

        <h2>اضــافة عقار جديد</h2>
        <p>
            الآن تستطيع حفظ بيانات العقار وإعادة إنشاء العقود عليها بكل سهولة
            ودون الحاجة لتعبئة البيانات
            <img src="{{ asset('website/asset/images/verified.svg') }}" alt="Verified Icon" />
        </p>
        
            <button class="add-property" id="add-property">
                اضـافة عقـار جديد
                <i class="fa-solid fa-plus"></i>
            </button>
        
    </div>

    <div class="property-list">
        @foreach ($real as $item)
            <div class="property-item">
                <div class="properties-info">
                    <div class="property-info">
                        <img src="{{ asset('website/asset/images/state-icon.svg') }}" alt="State Icon" />
                        <span>اسم العقار: {{ $item->name_real_estate ?? 'غير متوفر' }}</span>
                    </div>
                    <div class="property-info">
                        <img src="{{ asset('website/asset/images/state-owner-icon.svg') }}" alt="State Owner Icon" />
                        <span>مالك العقار: {{ $item->name_owner ?? 'غير متوفر' }}</span>
                    </div>
                    <div class="property-info">
                        <img src="{{ asset('website/asset/images/location-icon.svg') }}" alt="Location Icon" />
                        <span>موقع العقار: {{ $item->tenantEntityCity->name_trans ?? 'غير متوفر' }}</span>
                    </div>
                </div>

                <form class="delete-form" method="POST" action="{{ route('realestate.delete', $item->id) }}" style="position: absolute; top: 0; left: 0;">
                    @csrf
                     <button type="submit" class="delete-button" style="background: none; border: none; padding: 0; cursor: pointer;">
                        <img src="{{ asset('website/asset/images/delete-icon.svg') }}" alt="Delete Icon" class="delete-icon" />
                    </button>
                </form>

                <div class="separator"></div>
                <div class="property-actions">
                    <a href="{{ route('realestate.show', $item->id) }}" class="action-button">عرض و تعديل العقار</a>
                    <a href="{{ route('create.realUnit', $item->id) }}" class="action-button">أضافة وحدة للعقار</a>
                    <a href="{{ route('unit', $item->id) }}" class="action-button">عرض الوحدات</a>
                    <a href="{{ route('submit.contract_type', $item->id) }}" class="action-button" data-id="{{ $item->id }}">
                        انشاء عقد ايجار على هذا العقار
                    </a>
                </div>
            </div>
 
            <div class="overlay-delete" id="overlay-delete-{{ $item->id }}" style="display: none;"></div>
            <div class="delete-modal-delete" id="delete-modal-delete-{{ $item->id }}" style="display: none;">
                <i class="fa-solid fa-xmark close-icon-delete"></i>
                <img src="{{ asset('website/asset/images/recycle-icon.svg') }}" alt="" class="recycle-icon-delete" />
                <h2>تاكيد الحذف</h2>
                <p>هل انت متاكد من حذف العقار؟</p>
                <div class="button-container-delete">
                    <button class="cancel-delete">عودة</button>
                    <form action="{{ route('realestate.delete', $item->id) }}" id="delete-form-confirm" method="POST">
                        @csrf
                     
                        <button type="submit" class="confirm-delete" id="confirm-delete-button">تأكيد</button>
                    </form>
                </div>
            </div>

            <!-- Success Delete Modal -->
            <div class="overlay-success" style="display: none;"></div>
            <div class="success-delete-modal-success" style="display: none;">
                <i class="fa-solid fa-xmark close-icon-success"></i>
                <img src="{{ asset('website/asset/images/success-modal-icon.svg') }}" alt="" />
                <h2>تم حذف العقار بنجاح</h2>
                <a href="{{ route('realEstate') }}" class="confirm-success">العودة للرئيسية</a>
            </div>
            @endforeach
            

            <!-- Choose Contract Modal -->
            <div class="modal-overlay-choose" id="modal-choose-add" style="display: none;">
                <div class="modal-content-choose" id="chooseModal-add">
                    <div class="modal-header-choose">
                        <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-choose" />
                    </div>
                    <form action="{{ route('create.new.realEstate') }}" method="POST">
                        @csrf
                        <div class="modal-body modal-body-choose">
                            <img src="{{ asset('website/asset/images/choosecontracttype-icon.svg') }}" alt="Choose Contract Icon" />
                            <h1 class="modal-title-choose">اختر نوع العقار</h1>
                            <p class="modal-text-choose">اختر العقار الذي ترغب به ( سكني - تجاري )</p>

                            <div class="options-boxes-choose">
                                <div class="holder-choose">
                                    <label class="option-box-choose" data-contract-type="housing">
                                        <input type="radio" name="contract_type" value="housing" style="display: none;" required>
                                        <img src="{{ asset('website/asset/images/createcontract.svg') }}" alt="Residential Contract" />
                                        <h2>عقد ايجار سـكني</h2>
                                        <p>عمارة - شقة - فيلا - غرفة</p>
                                        <img class="selected-icon-choose" src="{{ asset('website/asset/images/solid-empty-circle.svg') }}" alt="Selected" />
                                    </label>
                                    <div class="bottom-text-choose">
                                    
                                        <img src="{{ asset('website/asset/images/small-grey-left-arrow.svg') }}" alt="Arrow" />
                                    </div>
                                </div>

                                <div class="holder-choose">
                                    <label class="option-box-choose" data-contract-type="commercial">
                                        <input type="radio" name="contract_type" value="commercial" style="display: none;" required>
                                        <img src="{{ asset('website/asset/images/choosecontract.svg') }}" alt="Commercial Contract" />
                                        <h2>عقد ايجار تجــاري</h2>
                                        <p>محل - سوق - فندق - مصنع</p>
                                        <img class="selected-icon-choose" src="{{ asset('website/asset/images/solid-empty-circle.svg') }}" alt="Unselected" />
                                    </label>
                                    <div class="bottom-text-choose">
                                    
                                        <img src="{{ asset('website/asset/images/small-grey-left-arrow.svg') }}" alt="Arrow" />
                                    </div>
                                </div>
                            </div>

                            <div class="contract-modal-footer-choose">
                                <a href="{{ route('realEstate') }}" class="back-choose">عوده</a>
                                <button type="submit" class="next-choose">
                                     اكمال  
                                    <i class="fa-solid fa-arrow-left-long"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    <div class="pagination-custom" style="display: flex; justify-content: center; align-items: center; margin-top: 20px;">
        @if ($real->lastPage() > 1)
            <ul class="page-list" style="list-style: none; display: flex; padding: 0;">
                {{-- Previous Page --}}
                <li class="page-item {{ ($real->currentPage() == 1) ? 'disabled' : '' }}" style="margin: 0 5px;">
                    <a href="{{ $real->previousPageUrl() }}" class="page-link" 
                       style="padding: 8px 12px; text-decoration: none; color: #333;">
                       &lt;
                    </a>
                </li>
    
                {{-- Page Numbers --}}
                @for ($i = 1; $i <= $real->lastPage(); $i++)
                    <li class="page-item {{ ($real->currentPage() == $i) ? 'active' : '' }}" style="margin: 0 5px;">
                        <a href="{{ $real->url($i) }}" class="page-link" 
                           style="padding: 8px 12px;  text-decoration: none; color: #333;
                                  {{ ($real->currentPage() == $i) ? 'background-color: #00695c; color: white; border-color: #00695c;' : '' }}">
                           {{ $i }}
                        </a>
                    </li>
                @endfor
    
                {{-- Next Page --}}
                <li class="page-item {{ ($real->currentPage() == $real->lastPage()) ? 'disabled' : '' }}" style="margin: 0 5px;">
                    <a href="{{ $real->nextPageUrl() }}" class="page-link" 
                       style="padding: 8px 12px;  text-decoration: none; color: #333;">
                       &gt;
                    </a>
                </li>
            </ul>
        @endif
    </div>
    

</section>

@endsection





<script>
    document.addEventListener("DOMContentLoaded", function() {

        // Sidebar toggle logic
        function initializeSidebarToggle() {
            const hamburger = document.querySelector(".hamburger");
            const closeBtn = document.querySelector(".close-btn");
            const sidebar = document.querySelector(".sidebar");

            if (hamburger && closeBtn && sidebar) {
                hamburger.addEventListener("click", function() {
                    sidebar.classList.add("open");
                });

                closeBtn.addEventListener("click", function() {
                    sidebar.classList.remove("open");
                });
            }
        }

        // Delete modal logic
        function initializeDeleteModalLogic() {
            const deleteIcons = document.querySelectorAll(".delete-button");
            const overlay = document.querySelector(".overlay-delete");
            const modal = document.querySelector(".delete-modal-delete");
            const closeIcon = document.querySelector(".close-icon-delete");
            const cancelDelete = document.querySelector(".cancel-delete");

            function showModal() {
                overlay.style.display = "block";
                modal.style.display = "block";
            }

            function hideModal() {
                overlay.style.display = "none";
                modal.style.display = "none";
            }

            deleteIcons.forEach(function(deleteIcon) {
                deleteIcon.addEventListener("click", function(event) {
                    event.preventDefault();
                    showModal();
                });
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
        }

        // Confirm delete logic
        function initializeConfirmDeleteLogic() {
            const confirmDeleteButton = document.querySelector(`#confirm-delete-button`);

            if (confirmDeleteButton) {
                confirmDeleteButton.addEventListener("click", function () {
                    const id = ""; // Add logic to get the appropriate id
                    document.querySelector(`#delete-modal-delete-${id}`).style.display = "none";
                    document.querySelector(`#overlay-delete-${id}`).style.display = "none";
    
                    document.querySelector('.overlay-success').style.display = "block";
                    document.querySelector('.success-delete-modal-success').style.display = "block";
                });
            }
        }

        // Modal logic for add-contract and choosing contract type
        function initializeModalLogic() {
            const addContractLinks = document.querySelectorAll(".add-contract");
            const modalOverlay = document.querySelector("#modal-choose");
            const modalOverlayAdd = document.querySelector("#modal-choose-add");
            const closeIcons = document.querySelectorAll(".close-icon-choose");
            const addPropertyButton = document.getElementById("add-property");
            const optionBoxes = document.querySelectorAll(".option-box-choose");

            function showModal() {
                modalOverlay.style.display = "flex";
            }

            function showAddModal() {
                modalOverlayAdd.style.display = "flex";
            }

            function hideModal() {
                modalOverlayAdd.style.display = "none";
            }

            // Handle 'Add Property' button logic
            function handleAddPropertyButtonClick() {
                if (addPropertyButton) {
                    addPropertyButton.addEventListener("click", function() {
                        localStorage.clear();
                        showAddModal();
                    });
                }
            }

            // Add contract link click event logic
            function initializeAddContractLinks() {
                addContractLinks.forEach(function(addContractLink) {
                    addContractLink.addEventListener("click", function(event) {
                        event.preventDefault();
                        showModal();
                    });
                });
            }

            // Logic for selecting contract type (option boxes)
            function initializeOptionBoxes() {
                if (optionBoxes) {
                    optionBoxes.forEach((box) => {
                        box.addEventListener("click", function() {
                            // Reset all option boxes
                            optionBoxes.forEach((box) => {
                                box.classList.remove("active");
                                const selectedIcon = box.querySelector(".selected-icon-choose");
                                if (selectedIcon) {
                                    selectedIcon.src =
                                        "{{ asset('website/asset/images/solid-empty-circle.svg') }}";
                                    selectedIcon.style.backgroundColor = "transparent";
                                    selectedIcon.style.width = "unset";
                                    selectedIcon.style.height = "unset";
                                    selectedIcon.style.padding = "unset";
                                }
                            });

                            // Activate the clicked option box
                            this.classList.add("active");
                            const selectedIcon = this.querySelector(".selected-icon-choose");
                            if (selectedIcon) {
                                selectedIcon.src =
                                    "{{ asset('website/asset/images/selected-icon.svg') }}";
                                selectedIcon.style.backgroundColor = "#319e90";
                                selectedIcon.style.width = "16px";
                                selectedIcon.style.height = "16px";
                                selectedIcon.style.padding = "5px";
                            }
                        });
                    });
                }
            }

            // Initialize all modal logic
            initializeAddContractLinks();
            handleAddPropertyButtonClick();

            if (closeIcons) {
                closeIcons.forEach((closeIcon) => {
                    closeIcon.addEventListener("click", hideModal);
                });
            }

            initializeOptionBoxes();
        }

        // Initialize all functions
        initializeSidebarToggle();
        initializeDeleteModalLogic();
        initializeConfirmDeleteLogic();
        initializeModalLogic();

    });
</script>
