 
 @extends('website.RealEstate.app')

 @section('title', 'الخطوة الأولى')
 
 @section('content')

      <h3 class="heading3-state">رحلتك الاجارية اصبحت أسهل</h3>
      <div class="info-box">
        <img src="{{asset('website/asset/images/sucess-icon.svg')}}" alt="" />
        <h2>تم حفظ بيانات العقار بنجاح</h2>
        <p>
          تستطيع الان انشاء عقود الايجار بكل سهولة ودون تعبئة بيانات العقار
          والمالك
        </p>
        <div class="links-buttons-action">
          <a href="{{ route('realEstate') }}">عرض العقارات</a>
          <a href="{{ route('create.realUnit', ['id' => $realEstate->id]) }}">اضافة وحدة على هذا العقار</a>
          {{-- <a href="{{ route('unit.real', ['id' => $realEstate->id]) }}">
            انشاء عقد ايجار على هذا العقار
            <img src="{{asset('website/asset/images/white-left-arrow.svg')}}" alt="" />
          </a> --}}

          {{-- <form action="{{ route('unit.real', ['id' => $realEstate->id]) }}" method="POST">
            @csrf
            <button type="submit" class="flex items-center">
                انشاء عقد ايجار على هذا العقار
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </form> --}}
        </div>
      </div>
    </section>
    <a class="help-text-state" href="">
      واجهتك مشكلة ؟كلمنا على واتساب
      <img src="{{asset('website/asset/images/whatsapp-icon.svg')}}" alt="" />
    </a>
  </body>
@endsection