@extends('website._layout.app')

@section('title', 'استعادة كلمة المرور')

@section('content')

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

     <form action="{{route('website.PostForget')}}" method="POST">
        @csrf
        <div class="links">
            <a href="{{ route('website.login') }}" class="active">تسجيل الدخول</a>
            <a href="{{ route('website.signup') }}">إنشاء حساب</a>
        </div>
        <input type="hidden" class="form-control" name="mobile" id="mobile" value="{{ $user->mobile }}" >

        <h2 class="hello-message">استعادة كلمة المرور</h2>
        <p class="intro">الرجاء ادخال كود التحقق الذي وصلك</p>
        <div class="form-group">
            <label for="code">كود التحقق *</label>
            <div class="input-group">
                <input
                    type="text"
                    id="code"
                    placeholder="الرجاء ادخل كود التحقق"
                    required
                    name="reset_password_code"
                />
                <img
                    src="{{ asset('website/asset/images/verify-code-icon.svg') }}"
                    alt="Verify Code Icon"
                    class="input-icon"
                />
            </div>
        </div>

        <button type="submit" class="submit-btn">تحقق</button>
    </form>
@endsection
