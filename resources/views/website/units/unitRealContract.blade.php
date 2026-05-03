@extends('website.auth.app')

@section('title', 'الوحدات')

@section('content')
<section class="real-state">
    <a href="{{ url()->previous() }}"> 
        <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" class="back-arrow" />
    </a>
    <div class="real-state-heading">
        <img src="{{ asset('website/asset/images/property-icon.svg') }}" alt="" />
        <h2>الوحدات</h2>
        <p>تستطيع تعديل واستعراض وإضافة الوحدات</p>
        @if($contract->contract_type=='housing')

        <p class="alert-text">لقد قمت بأختيار عقد ايجار سكني ستظهر لك الواحدات السكنية فقط</p>
      @else
      <p class="alert-text">لقد قمت بأختيار عقد ايجار تجاري ستظهر لك الواحدات التجارية فقط</p>

      @endif

        <!-- Create New Unit Link (outside of the loop) -->
        <a href="{{ route('create.realUnit', ['id' => $userReal->id]) }}" class="edit-unit">
            انشاء وحدة جديدة
            <i class="fa-solid fa-plus"></i>
            
        </a>
    </div>

    <div class="container-unit">
        @foreach ($units as $unit)
        <!-- Unit -->
        <div class="section-unit">
            <i class="fa-solid fa-xmark x-button"></i>
            <div class="edit-header-unit">
                <h2>رقم الوحده {{ $unit->id }}</h2>
                <a href="{{ url('edit/unit', ['id' => $unit->id]) }}"> تعديل الوحده <i class="fa-solid fa-pen"></i></a>
            </div>
            <div class="separator"></div>
                <form action="{{ route('submit.units', ['uuid' => $contract->uuid, 'real_id' => $contract->real_id, 'id' => $unit->id]) }}" method="POST">
            {{-- <form action="" method="POST"> --}}
                
                @csrf
                <button type="submit" class="button-link flex items-center">
                    انشاء عقد ايجار على هذي الوحدة
                    <i class="fa-solid fa-arrow-left ml-2"></i>
                </button>
            </form>
            <ul class="info-list-unit">
                <li><i class="fa-solid fa-circle-check"></i> نوع الوحدة: {{ $unit->unitType ? $unit->unitType->name_ar : 'غير محدد' }}</li>
                <li><i class="fa-solid fa-circle-check"></i> استخدام الوحدة: {{ $unit->unitUsage ? $unit->unitUsage->name_ar : 'غير محدد' }}</li>
                <li><i class="fa-solid fa-circle-check"></i> رقم الطابق: {{ $unit->floor_number ?? 'غير متوفر' }}</li>
                <li><i class="fa-solid fa-circle-check"></i> مساحة الوحدة: {{ $unit->unit_area ?? 'غير متوفر' }}</li>
                <li><i class="fa-solid fa-circle-check"></i> عدد مكيفات (شباك): {{ $unit->window_ac ?? 'غير متوفر' }}</li>
                <li><i class="fa-solid fa-circle-check"></i> عدد مكيفات (سبليت): {{ $unit->split_ac ?? 'غير متوفر' }}</li>
                @if($userReal->contract_type == 'housing')
                <li>
                    <i class="fa-solid fa-circle-check"></i> 
                    عدد دورات المياه: {{ $unit->The_number_of_toilets ?? 'غير محدد' }}
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i> 
                    رقم عداد الكهرباء (إن وجد): {{ $unit->electricity_meter_number ?? 'غير متوفر' }}
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i> 
                    رقم عداد المياه (إن وجد): {{ $unit->water_meter_number ?? 'غير متوفر' }}
                </li>
            @endif
            
            @if($userReal->contract_type == 'commercial')
                <li>
                    <i class="fa-solid fa-circle-check"></i> 
                    التأجير من الباطن: {{ $unit->sub_delay == 1 ? 'نعم' : 'لا' }}
                </li>
            @endif
            
                <li><i class="fa-solid fa-circle-check"></i> اضافه بتاريخ: {{ $unit->created_at ? $unit->created_at->format('Y-m-d H:i:s') : 'غير متوفر' }}</li>
            </ul>
        </div>
        @endforeach
    </div>
</section>
@endsection
