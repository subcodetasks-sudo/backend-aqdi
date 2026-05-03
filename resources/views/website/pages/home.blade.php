@extends('website.auth.app')

@section('title', 'الرئيسيه')

@section('content')

<div class="content-home">
    <section class="info-section">
        <p class="verified-contract">
            عقدك الموثق من شبكة ايجار خلال 30 دقيقة
            <img
                src="{{ asset('website/asset/images/verified.svg') }}"
                alt="Check Icon"
                class="check-icon"
            />
        </p>
        
        <h1 class="contract-heading">
            عــــــــــقد إيـــــــــــــــــجار<br />
            الكتــــرونــــي <span>موثّــــــق</span>
        </h1>
        <p class="contract-description">
           من خلالنا تستطيع انجاز جميع تعاملاتك الايجارية دون الحاجة  الى مكتب عقار 
        اذا كنت مالك عقار او مستأجر او مكتب خدمات او وسيط عقاري 
        قم بأنشاء عقدي ايجار الكتروني نحن خيارك الأكثر سرعة ومن أي مكان في المملكة ، سواء كنت تحتاجه لحساب المواطن أو الضمان الاجتماعي أو حافز أو لأي أغراض أخرى ...
        </p>
        <div class="action-buttons">
            <a href="#" class="btn housing" id="openFirstModal">
                 إنشاء عقد
                <img
                    src="{{ asset('website/asset/images/arrow-icon.svg') }}"
                    alt="Arrow Icon"
                    class="arrow-icon"
                />
            </a>
            <!--<a href="#" class="btn commerical" id="openSecondModal">-->
            <!--    إنشاء عقد إيجار تجاري-->
            <!--    <img-->
            <!--        src="{{ asset('website/asset/images/arrow-icon.svg') }}"-->
            <!--        alt="Arrow Icon"-->
            <!--        class="arrow-icon"-->
            <!--    />-->
            <!--</a>-->
        </div>
    </section>

    @include('website.pages.info')

    <!-- Modals Part  -->
    <div class="modal-overlay-create">
        <div class="modal-content-create">
            <div class="modal-header-create">
                <img
                    src="{{ asset('website/asset/images/navy-x-icon.svg') }}"
                    alt="Close"
                    class="close-icon-create"
                />
            </div>

            <!-- Modal Body -->
            <div class="modal-body-create">
                <img
                    src="{{ asset('website/asset/images/ejar-icon.svg') }}"
                    alt="Image 1"
                    class="modal-image-create"
                />
                <h1 class="modal-title-create">عقد ايجار إلكتروني من منصة ايجار</h1>
                <p class="modal-text-create">
                    منصة عقدي لقطاع إيجاري مزدهر
                 </p>
                <div class="options-boxes-create">
                    <div id="create-contract-box" class="option-box-create active">
                        <img src="{{ asset('website/asset/images/createcontract.svg') }}" alt="" />
                        <h2>إنشاء عقد إيجار</h2>
                        <p>انشاء عقد ايجار على عقار غير محفوظ</p>
                        <div class="selected-icon-create">
                            <img src="{{ asset('website/asset/images/selected-icon.svg') }}" alt="" />
                        </div>
                    </div>
                    <div id="choose-property-box" class="option-box-create">
                        <img src="{{ asset('website/asset/images/choosecontract.svg') }}" alt="" />
                        <h2>اختيار عقار وانشاء عقد</h2>
                        <p>اختر احد عقاراتك التي سبق حفظها لتسهيل عمليتك الإيجارية</p>
                        <div class="selected-icon-create">
                            <img src="{{ asset('website/asset/images/selected-icon.svg') }}" alt="" />
                        </div>
                    </div>
                </div>
                
                <div class="contract-modal-footer-create">
                    <a href="#" class="back-create" id="back-button">عوده</a>
                    <img src="{{ asset('website/asset/images/dashes.svg') }}" alt="" class="dashes-create" />
                    <a href="#" class="next-create" id="next-button">متابعه
                        <img src="{{ asset('website/asset/images/white-left-arrow.svg') }}" alt="" />
                    </a>
                </div>
                
            </div>
        </div>
    </div>

         <!-- Ensured only one modal in the page -->
         <div class="modal-overlay-choose" style="display: none;">
            <div class="modal-content-choose">
                <div class="modal-header-choose">
                    <img src="{{ asset('website/asset/images/navy-x-icon.svg') }}" alt="Close" class="close-icon-choose" />
                </div>
                <form action="{{ route('submit.new') }}" method="POST">
                    @csrf
                    <div class="modal-body modal-body-choose">
                        <img src="{{ asset('website/asset/images/choosecontracttype-icon.svg') }}" alt="Choose Contract Icon" />
                        <h1 class="modal-title-choose">اختر نوع عقد الايجار</h1>
                        <p class="modal-text-choose">اختر العقد الذي ترغب به ( سكني - تجاري )</p>
        
                        <div class="options-boxes-choose">
                            <div class="holder-choose">
                                <label class="option-box-choose" data-contract-type="housing">
                                    <input type="radio" name="contract_type" value="housing" style="display: none" required>
                                    <img src="{{ asset('website/asset/images/createcontract.svg') }}" alt="Residential Contract" />
                                    <h2>عقد ايجار سـكني</h2>
                                    <p>عمارة - شقة - فيلا - غرفة</p>
                                    <img class="selected-icon-choose" src="{{ asset('website/asset/images/solid-empty-circle.svg') }}" alt="Selected" />
                                </label>
                                <!--<div class="bottom-text-choose">-->
                                 
                                <!--    <img src="{{ asset('website/asset/images/small-grey-left-arrow.svg') }}" alt="Arrow" />-->
                                <!--</div>-->
                            </div>
        
                            <div class="holder-choose">
                                <label class="option-box-choose" data-contract-type="commercial">
                                    <input type="radio" name="contract_type" value="commercial" style="display: none" required>
                                    <img src="{{ asset('website/asset/images/choosecontract.svg') }}" alt="Commercial Contract" />
                                    <h2>عقد ايجار تجــاري</h2>
                                    <p>محل - سوق - فندق - مصنع</p>
                                    <img class="selected-icon-choose" src="{{ asset('website/asset/images/solid-empty-circle.svg') }}" alt="Unselected" />
                                </label>
                                <!--<div class="bottom-text-choose">-->
                                  
                                <!--    <img src="{{ asset('website/asset/images/small-grey-left-arrow.svg') }}" alt="Arrow" />-->
                                <!--</div>-->
                            </div>
                        </div>
        
                        <div class="contract-modal-footer-choose">
                            <a href="{{ route('realEstate') }}" class="back-choose">عوده</a>
                            <button type="submit" class="next-choose">
                                  التالي
                                <i class="fa-solid fa-arrow-left-long"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

@endsection

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // First Modal Elements
        const openFirstModalLink = document.getElementById("openFirstModal");
        // const openSecondModalLink = document.getElementById("openSecondModal");
        const firstModalOverlay = document.querySelector(".modal-overlay-create");
        const closeFirstModalIcon = document.querySelector(".close-icon-create");
        const firstOptionBoxes = document.querySelectorAll(".option-box-create");
        const continueToSecondModalLink = document.querySelector(".next-create");
        const backToFirstModalLink = document.querySelector(".back-create");

        // Second Modal Elements
        const secondModalOverlay = document.querySelector(".modal-overlay-choose");
        const closeSecondModalIcon = document.querySelector(".close-icon-choose");
        const secondOptionBoxes = document.querySelectorAll(".option-box-choose");
        const backToFirstModalFromSecondLink = document.querySelector(".back-choose");

        // Create/Choose Property Elements
        const createContractBox = document.getElementById('create-contract-box');
        const choosePropertyBox = document.getElementById('choose-property-box');
        const nextButton = document.getElementById('next-button');
        let selectedOption = null;

        // Functions
        function closeModal(modalOverlay) {
            modalOverlay.style.display = "none";
        }

        function showModal(modalOverlay) {
            modalOverlay.style.display = "flex";
        }

        // First Modal Events
        openFirstModalLink.addEventListener("click", function (event) {
            event.preventDefault();
            showModal(firstModalOverlay);
        });



        closeFirstModalIcon.addEventListener("click", function () {
            closeModal(firstModalOverlay);
        });

        firstOptionBoxes.forEach((box) => {
            box.addEventListener("click", function () {
                firstOptionBoxes.forEach((box) => box.classList.remove("active"));
                this.classList.add("active");
            });
        });

        continueToSecondModalLink.addEventListener("click", function (event) {
            event.preventDefault();
            closeModal(firstModalOverlay);
            showModal(secondModalOverlay);
        });

        backToFirstModalLink.addEventListener("click", function (event) {
            event.preventDefault();
            closeModal(firstModalOverlay);
        });

        // Second Modal Events
        closeSecondModalIcon.addEventListener("click", function () {
            closeModal(secondModalOverlay);
        });

        secondOptionBoxes.forEach((box) => {
            box.addEventListener("click", function () {
                secondOptionBoxes.forEach((box) => {
                    box.classList.remove("active");
                    const selectedIcon = box.querySelector(".selected-icon-choose");
                    if (selectedIcon) {
                        selectedIcon.src = "{{asset('website/asset/images/solid-empty-circle.svg')}}";
                        selectedIcon.style.backgroundColor = "transparent";
                        selectedIcon.style.width = "unset";
                        selectedIcon.style.height = "unset";
                        selectedIcon.style.padding = "unset";
                    }
                });

                // Activate the clicked box and change the icon
                this.classList.add("active");
                const selectedIcon = this.querySelector(".selected-icon-choose");
                if (selectedIcon) {
                    selectedIcon.src = "{{asset('website/asset/images/selected-icon.svg')}}";
                    selectedIcon.style.backgroundColor = "#319e90";
                    selectedIcon.style.width = "16px";
                    selectedIcon.style.height = "16px";
                    selectedIcon.style.padding = "5px";
                }
            });
        });

        backToFirstModalFromSecondLink.addEventListener("click", function (event) {
            event.preventDefault();
            closeModal(secondModalOverlay);
            showModal(firstModalOverlay);
        });

        // Create Contract/Choose Property Logic
        createContractBox.addEventListener('click', function () {
            selectedOption = 'create_contract';
            createContractBox.classList.add('active');
            choosePropertyBox.classList.remove('active');
        });

        choosePropertyBox.addEventListener('click', function () {
            selectedOption = 'choose_property';
            choosePropertyBox.classList.add('active');
            createContractBox.classList.remove('active');
        });

        nextButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (selectedOption === 'create_contract') {
                // Logic for creating contract
            } else if (selectedOption === 'choose_property') {
                closeModal(secondModalOverlay);
                window.location.href = '{{ route('realEstate') }}';
            }
        });

        document.getElementById('back-button').addEventListener('click', function (e) {
            e.preventDefault();
            // Logic for the back button (if needed)
        });
    });
</script>

