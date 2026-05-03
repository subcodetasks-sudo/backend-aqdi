
@extends('website.Contract.layout.app')

 
@section('content')

    <h3 class="heading3-state">رحلتك الإيجارية أصبحت أسهل</h3>
    <h1 class="heading1-state">تسعير الخدمات</h1>
    <p class="description-state">سعر إنشاء العقد</p>
    
     <div class="form-content-state">
        {{-- <i class="fa-solid fa-money-bill-wave modal-image-price"></i> --}}
        <div class="prices-container" style="gap: 30px; margin-top: 30px">

            @foreach($pricing as $pricingValue)
            <div class="price-line">
                <p class="label">{{$pricingValue->name_trans}} </p>
                <p><span class="price-line-number">{{$pricingValue->price}}</span> ريال سعودي</p>
            </div>
            @endforeach
            
          
        </div>
         <div class="buttons-state">
            <a href="{{ route('website.home') }}" class="back-button-state">عودة</a>
            <a href="{{ route('paperwork', ['uuid' => $contract->uuid, 'real_id' => $real->id, 'id' => $units->id]) }}" class="next-button-state">
              يلا بسم الله
                <i class="fa-solid fa-arrow-left-long"></i>
            </a>
        </div>
    </div>

    <a class="help-text-state" href="https://wa.me/your-number" target="_blank">
        واجهتك مشكلة؟ كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
    </a>

@endsection

    