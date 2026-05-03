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
                    @method('DELETE')
                    <button type="submit" class="delete-button" style="background: none; border: none; padding: 0; cursor: pointer;">
                        <img src="{{ asset('website/asset/images/delete-icon.svg') }}" alt="Delete Icon" class="delete-icon" />
                    </button>
                </form>

                <div class="separator"></div>
                <div class="property-actions">
                    <a href="{{ route('realestate.show', $item->id) }}" class="action-button">عرض و تعديل العقار</a>
                    <a href="{{ route('create.realUnit', $item->id) }}" class="action-button">أضافة وحدة للعقار</a>
                    <a href="{{ route('unit', $item->id) }}" class="action-button">عرض الوحدات</a>
                    <a href="{{ route('contract.unit.real', $item->id) }}" class="action-button add-contract" data-id="{{ $item->id }}">
                        انشاء عقد ايجار على هذا العقار
                    </a>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="overlay" id="overlay-delete-{{ $item->id }}" style="display: none;"></div>
            <div class="delete-modal-delete" id="delete-modal-delete-{{ $item->id }}" style="display: none;">
                <i class="fa-solid fa-xmark close-icon-delete"></i>
                <img src="{{ asset('website/asset/images/recycle-icon.svg') }}" alt="" class="recycle-icon-delete" />
                <h2>تاكيد الحذف</h2>
                <p>هل انت متاكد من حذف العقار؟</p>
                <div class="button-container-delete">
                    <button class="cancel-delete">عودة</button>
                    <form action="{{ route('realestate.delete', $item->id) }}" id="delete-form-confirm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="confirm-delete">تأكيد</button>
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

            <!-- Choose Contract Modal -->
            <div class="modal-overlay-choose" id="modal-choose-{{ $item->id }}" style="display: none;">
                <div class="modal-content-choose" id="chooseModal">
                    <div class="modal-header-choose">
                        <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-choose" />
                    </div>
                    <form action="{{ route('create.new.realEstate') }}" method="POST">
                        @csrf
                        <div class="modal-body modal-body-choose">
                            <img src="{{ asset('website/asset/images/choosecontracttype-icon.svg') }}" alt="Choose Contract Icon" />
                            <h1 class="modal-title-choose">اختر نوع عقد الايجار</h1>
                            <p class="modal-text-choose">اختر العقد الذي ترغب به ( سكني - تجاري )</p>

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
                                        <p>قيمة العقد السكني</p>
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
                                        <p>قيمة العقد التجاري</p>
                                        <img src="{{ asset('website/asset/images/small-grey-left-arrow.svg') }}" alt="Arrow" />
                                    </div>
                                </div>
                            </div>

                            <div class="contract-modal-footer-choose">
                                <a href="{{ route('realEstate') }}" class="back-choose">عوده</a>
                                <button type="submit" class="next-choose">
                                    أختر الوحدة
                                    <i class="fa-solid fa-arrow-left-long"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Choose Modal Not Connected With the For each to let us create our first RealEstat-->
    <div class="modal-overlay-choose" id="modal-choose" style="display: none;">
                <div class="modal-content-choose" id="chooseModal">
                    <div class="modal-header-choose">
                        <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-choose" />
                    </div>
                    <form action="{{ route('create.new.realEstate') }}" method="POST">
                        @csrf
                        <div class="modal-body modal-body-choose">
                            <img src="{{ asset('website/asset/images/choosecontracttype-icon.svg') }}" alt="Choose Contract Icon" />
                            <h1 class="modal-title-choose">اختر نوع عقد الايجار</h1>
                            <p class="modal-text-choose">اختر العقد الذي ترغب به ( سكني - تجاري )</p>

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
                                        <p>قيمة العقد السكني</p>
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
                                        <p>قيمة العقد التجاري</p>
                                        <img src="{{ asset('website/asset/images/small-grey-left-arrow.svg') }}" alt="Arrow" />
                                    </div>
                                </div>
                            </div>

                            <div class="contract-modal-footer-choose">
                                <a href="{{ route('realEstate') }}" class="back-choose">عوده</a>
                                <button type="submit" class="next-choose">
                                    أختر الوحدة
                                    <i class="fa-solid fa-arrow-left-long"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
</section>

@endsection
 
 <script>


    document.addEventListener("DOMContentLoaded", function() {
        // Sidebar toggle logic
        document.querySelector(".hamburger").addEventListener("click", function() {
            document.querySelector(".sidebar").classList.add("open");
        });

        document.querySelector(".close-btn").addEventListener("click", function() {
            document.querySelector(".sidebar").classList.remove("open");
        });

        // Modal logic for delete functionality
        (function() {
            const deleteIcons = document.querySelectorAll(".delete-icon");
            const overlay = document.getElementById("overlay-delete");
            const modal = document.getElementById("delete-modal-delete");
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
        })();
        


        // Modal logic for add-contract functionality
        (function() {
            const addContractLinks = document.querySelectorAll(".add-contract");
            const modalOverlay = document.querySelector(".modal-overlay-choose");
            const closeIcon = document.querySelector(".close-icon-choose");
            const addPropertyButton = document.getElementById("add-property");

            function showModal() {
                modalOverlay.style.display = "flex";
            }

            function hideModal() {
                modalOverlay.style.display = "none";
            }

            addContractLinks.forEach(function(addContractLink) {
                addContractLink.addEventListener("click", function(event) {
                    event.preventDefault(); // Prevent default link behavior
                    showModal();
                });
            });

            if (closeIcon) {
                closeIcon.addEventListener("click", hideModal);
            }
            if (modalOverlay) {
                modalOverlay.addEventListener("click", function(event) {
                    const modalContent = document.querySelector(".modal-content-choose");
                    if (!modalContent.contains(event.target)) {
                        hideModal();
                    }
                });
            }
    if (addPropertyButton) {
        addPropertyButton.addEventListener("click", function() {
            localStorage.clear();
            showModal();
        });
    }
    // End of updated part
        })();
        

        // Modal logic for choosing contract type functionality
        (function() {
            const modalOverlay = document.querySelector(".modal-overlay-choose");
            const closeIcon = document.querySelector(".close-icon-choose");
            const optionBoxes = document.querySelectorAll(".option-box-choose");

            function closeModal() {
                modalOverlay.style.display = "none";
            }

            if (closeIcon) {
                closeIcon.addEventListener("click", closeModal);
            }
            if (modalOverlay) {
                modalOverlay.addEventListener("click", function(event) {
                    const modalContent = document.querySelector(".modal-content-choose");
                    if (!modalContent.contains(event.target)) {
                        closeModal();
                    }
                });
            }
            if (optionBoxes) {
                optionBoxes.forEach((box) => {
                    box.addEventListener("click", function() {
                        optionBoxes.forEach((box) => {
                            box.classList.remove("active");
                            const selectedIcon = box.querySelector(
                                ".selected-icon-choose");
                            if (selectedIcon) {
                                selectedIcon.src =
                                    "{{ asset('website/asset/images/solid-empty-circle.svg') }}";
                                selectedIcon.style.backgroundColor = "transparent";
                                selectedIcon.style.width = "unset";
                                selectedIcon.style.height = "unset";
                                selectedIcon.style.padding = "unset";
                            }
                        });

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
        })();
    });
</script>
