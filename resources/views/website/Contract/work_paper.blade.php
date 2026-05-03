@extends('website.Contract.layout.app')

@section('title', 'الخطوه الأولي')

@section('content')

        <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
        <h1 class="heading1-state">طلبات قبل أن نبدأ</h1>
        <p class="description-state">لخدمتك بشكل سريع ، جهّز التالي:</p>
        <div class="requests-holder">
            @foreach ($paperWorks as $paperWork)
                <div class="p-request">
                    <div class="deco"></div>
                    <p class="request">{{ $paperWork->name_trans }}</p>
                </div>
            @endforeach
        </div>
        <!-- Next Button -->
      
        <div class="buttons-state">
            <a type="button" href="{{ url()->previous() }}" class="back-button-state"
              >عودة</a
            >
            <a href="{{ route('step1', ['uuid' => $contract->uuid]) }}" id="clearStorageButton">

            <button type="submit"  class="next-button-state">
              يلا بسم الله
              <i class="fa-solid fa-arrow-left-long"></i>
            </button>
        </a>

        </div>
    </section>
   <a class="help-text-state"    href="https://wa.me/{{ $setting->whatsapp_contract ?? '+966597500014' }}" >
        واجهتك مشكلة ؟ اطلب عقدك من خلال ( واتساب )
        <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp Icon" style="width: 20px; height: 20px; margin-left: 5px;" />
    </a>

<script>
document.getElementById("clearStorageButton").addEventListener("click", function() {
    localStorage.clear();   // Clear the local storage
});
</script>

    <style>
        .next-button-container {
            text-align: center;
            margin-top: 20px;
        }

        .next-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #1b8769;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        
    </style>
@endsection