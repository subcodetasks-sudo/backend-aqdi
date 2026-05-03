@extends('website.Contract.layout.app')

@section('content')

    <h3 class="heading3-state">رحلتك الإيجارية أصبحت أسهل</h3>
    <h1 class="heading1-state">تسعير الخدمات</h1>
    <p class="description-state">سعر إنشاء العقد</p>

    <div class="form-content-state">
        <div class="prices-container" style="gap: 30px; margin-top: 30px">
            @foreach($pricing as $pricingValue)
                <div class="price-line">
                    <p class="label">{{ $pricingValue->name_trans }} </p>
                    <p><span class="price-line-number">{{ $pricingValue->price }}</span> ريال سعودي</p>
                </div>
            @endforeach

            @foreach($contractPeriod as $period)
                <div class="price-line">
                    <p class="label">{{ $period->period }} </p>
                    <p><span class="price-line-number">{{ $period->price }}</span> ريال سعودي</p>
                </div>
            @endforeach
        </div>

        <div class="buttons-state">
            <a href="{{ route('website.home') }}" class="back-button-state">عودة</a>
            <a href="{{ route('paperwork', $contract->uuid) }}" class="next-button-state">
                يلا بسم الله
                <i class="fa-solid fa-arrow-left-long"></i>
            </a>
        </div>
    </div>

    <a class="help-text-state" href="https://wa.me/{{ $setting->whatsapp_contract }}" target="_blank">
        واجهتك مشكلة ؟ اطلب عقدك من خلال ( واتساب )
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp Icon" style="width: 20px; height: 20px; margin-left: 5px;" />
    </a>

@endsection
