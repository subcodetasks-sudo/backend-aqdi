@extends('website._layout.app')

@section('content')
    <!-- Start Real Estate Section -->
    <section class="typeContract py-5">
        <div class="container">
            <div class="text-center textHead mt-5">
                <h3 class="mb-4">{{ __('website.start_realEstate') }}</h3>
                <p>{{ __('website.start_realEstate2') }}</p>
            </div>
            <!-- Form Submit -->
            <div class="row mt-5">
                <div class="col-12 col-md-6 text-center">
                    <a href="{{ route('realEstate') }}">
                        <button type="button" class="btn btn-primary mt-3 mb-4">{{ __('website.have_realestate') }}</button>
                    </a>
                    <br>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#realContractModal" class="optionChoose">توضيح</a>
                </div>
                <div class="col-12 col-md-6 text-center">
                    <a href="{{ route('create.step1.realEstate') }}">
                        <button type="button" class="btn btn-primary mt-3 mb-4">{{ __('website.not_have_realestate') }}</button>
                    </a>
                    <br>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#createContractModal" class="optionChoose">توضيح</a>
                </div>
                <div class="col-12 text-center mt-5">
                    <a href="{{ route('contract.new') }}">
                        <button type="button" id="submit" class="btn btn-secondary">{{ __('website.skip') }}</button>
                    </a> 
                    <br>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#ContractModal" class="optionChoose">توضيح</a>
                </div>
            </div>
        </div>
    </section>
    <!-- End Real Estate Section -->

    <!-- Explanation Modal Create a real -->
    <div class="modal fade" id="createContractModal" tabindex="-1" aria-labelledby="createContractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createContractModalLabel">
                        <i class="fa-solid fa-exclamation"></i> توضيح:
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>بعد ان تقوم بأضافة العقار تسطيع مستقبلا بانشاء العقود بدون إضافة بيانات العقار مرة أخرى فقط تقوم بتعبئة
بيانات المستأجر ومدة العقد والبيانات المالية للعقد وذالك لتسهيل عملياتك الجارية</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Explanation Modal for existing real estate -->
    <div class="modal fade" id="realContractModal" tabindex="-1" aria-labelledby="realContractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="realContractModalLabel">
                        <i class="fa-solid fa-exclamation"></i> توضيح:
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>نسمح لك بالانتقال الان الي العقارات الخاصه بك واختيار اي عقار منهم تريد عمل عقد له واختيار الوحدة مما يسهل عليك الكثير من الوقت في عمل العقد</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Explanation Modal for new contract -->
    <div class="modal fade" id="ContractModal" tabindex="-1" aria-labelledby="ContractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ContractModalLabel">
                        <i class="fa-solid fa-exclamation"></i> توضيح:
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>انتقل الان لانشاء عقد جديد غير مرتبط باي عقار لديك</p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .textHead {
        margin-bottom: 30px;
    }

    .btn-primary {
        background-color: #007bff;
        border: none;
        padding: 10px 20px;
        font-size: 1rem;
    }

    .optionChoose {
        color: #007bff;
        text-decoration: underline;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .btn-primary, .btn-secondary {
            width: 100%;
            margin-top: 10px;
        }
    }
</style>
@endpush
