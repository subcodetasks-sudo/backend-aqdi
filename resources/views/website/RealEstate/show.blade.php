@extends('website.auth.app')

@section('title', 'الرئيسيه')

@section('content')
<section class="real-state">
  <a href="{{ url()->previous() }}">
    <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" class="back-arrow" />
</a>

  <div class="real-state-heading">
    <img src="{{ asset('website/asset/images/property-icon.svg') }}" alt="" />
    <h2>معلومات العقار</h2>
    <p>عقدك الموثق من شبكة ايجار خلال 30 دقيقة</p>
    <a href="#" class="edit-property">
    {{-- <a href="{{ route('realestate.edit', ['id' => $realEstate->id]) }}" class="edit-property"> --}}
      تعديل معلومات هذا العقار
    </a>
  </div>

  <div class="container-edit">
    <div class="section-edit">
      <h2 class="edit-header-edit">
        
        بيانات العقار
        <a href="{{ route('realestate.step1.edit',[$realEstate->id]) }}">
          <i class="fa-solid fa-pen-to-square"></i>
        </a>
      </h2>
      
      <div class="separator"></div>
      <ul class="info-list-edit">
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          اسم العقار: {{ $realEstate->name_real_estate ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          نوع الصك:
          @if ($realEstate->instrument_type == "old_handwritten")
            صك مكتوب بخط اليد 
          @elseif ($realEstate->instrument_type == "strong_argument")
            صك السجل العقاري 
          @elseif ($realEstate->instrument_type == "electronic")
            الالكتروني 
          @endif
        </li>

        @if($realEstate->instrument_type == 'strong_argument')
          <li>
            <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
            رقم السجل العقاري: {{ $realEstate->real_estate_registry_number ?? 'غير متوفر' }}
          </li>
          <li>
            <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
            تاريخ التسجيل الأول: {{ $realEstate->date_first_registration ?? 'غير متوفر' }}
          </li>
        @endif

        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          نوع العقار: {{ optional($realEstate->propertyType)->name_ar ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          استخدام العقار: {{ optional($realEstate->propertyUsages)->name_ar ?? 'غير متوفر' }} 
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          عدد الطوابق بالعقار: {{ $realEstate->building_number ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          عدد الوحدات: {{ $realEstate->number_of_units_in_realestate ?? 'غير متوفر' }}
        </li>
      </ul>
    </div>

    <div class="section-edit">
      <h2 class="edit-header-edit">بيانات المالك
        <a href="{{ route('realestate.step3.edit',[$realEstate->id]) }}">
        <i class="fa-solid fa-pen-to-square"></i>
        </a>
      </h2>
      <div class="separator"></div>
      <ul class="info-list-edit">
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          اسم المالك: {{ $realEstate->name_owner ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          رقم هوية المالك: {{ $realEstate->property_owner_id_num   ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          تاريخ ميلاد المالك: {{ $realEstate-> 	property_owner_dob_hijri ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          رقم الجوال المالك: {{ $realEstate->property_owner_mobile  ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          الايبان البنكي: {{ $realEstate->property_owner_mobile  ?? 'غير متوفر' }}
        </li>
      </ul>
    </div>

    <div class="section-edit">
      <h2 class="edit-header-edit">العنوان الوطني
        <a href="{{ route('realestate.step2.edit',[$realEstate->id]) }}">
       
        <i class="fa-solid fa-pen-to-square"></i>
        </a>
      </h2>
      <div class="separator"></div>
      <ul class="info-list-edit">
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          المنطقه: {{ optional($realEstate->tenantEntityRegion)->name_ar ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          المدينه: {{ optional($realEstate->tenantEntityCity)->name_ar ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          الحي: {{ $realEstate->neighborhood ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          اسم الشارع: {{ $realEstate->street ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          رقم المبنى: {{ $realEstate->building_number ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          الرمز البريدي: {{ $realEstate->postal_code ?? 'غير متوفر' }}
        </li>
        <li>
          <img src="{{ asset('website/asset/images/check-circle-white.svg') }}" alt="Check Icon" />
          الرقم الإضافي: {{ $realEstate->extra_figure ?? 'غير متوفر' }}
        </li>
      </ul>
    </div>
  </div>
</section>

<script>
  document.querySelector(".hamburger").addEventListener("click", function () {
    document.querySelector(".sidebar").classList.add("open");
  });

  document.querySelector(".close-btn").addEventListener("click", function () {
    document.querySelector(".sidebar").classList.remove("open");
  });
</script>
@endsection
