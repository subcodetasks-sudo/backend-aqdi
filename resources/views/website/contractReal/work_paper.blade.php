<?php $Works = App\Models\Paperwork::all() ?>
@extends('website.Contract.layout.app')

@section('title', 'الخطوه الأولي')

@section('content')

    <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
    <h1 class="heading1-state">طلبات قبل أن نبدأ</h1>
    <p class="description-state">لخدمتك بشكل سريع ، جهّز التالي:</p>
    <div class="requests-holder">
        @if ($paperWorks && $Works->isNotEmpty())
        @foreach ($paperWorks as $paperWork)
                <div class="p-request">
                    <div class="deco"></div>
                    <p class="request">{{ $paperWork->name_trans }}</p>
                </div>
            @endforeach
    @else
        <p>لم تتم اضافة اوراق عمل بعد</p> 
    @endif
    
    </div>
    <!-- Next Button -->

    <div class="buttons-state">
        <a type="button" href="{{ url()->previous() }}" class="back-button-state">عودة</a>
        <a href="{{ route('real.step1', ['uuid' => $contract->uuid, 'real_id' => $real->id, 'id' => $units->id]) }}">

            <button type="submit" id="clearStorageButton" class="next-button-state">
                يلا بسم الله
                <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </a>

    </div>
    </section>
    <a class="help-text-state" href="">
        واجهتك مشكلة ؟كلمنا على واتساب
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="" />
    </a>

    <script>
        document.getElementById("clearStorageButton").addEventListener("click", function() {
    localStorage.clear();
});

             </script>


@endsection
