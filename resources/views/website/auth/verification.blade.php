@extends('website._layout.app')

@section('title', 'كود التحقق')

@section('content')


    <!-- Handle success messages -->
    @if(session('success'))
    <script>
        document.getElementById("verificationForm").addEventListener("submit", function (event) {
            event.preventDefault();
            window.location.href = "{{ route('website.newPassword') }}";
        });
    </script> 
    @endif

    <form id="verificationForm" action="{{ route('website.sendVerification') }}" method="POST">
        @csrf
        <div class="links">
            <a href="{{ route('website.login') }}">تسجيل الدخول</a>
            <a href="{{ route('website.signup') }}">إنشاء حساب</a>
        </div>

        <!-- Hidden mobile field with user's mobile value -->
        <input type="number" class="form-control" name="mobile" id="mobile" value="{{ $user->mobile }}" hidden>

        <!-- Error message for verification_code -->
        @error('verification_code')
        <div class="error-message">{{ $message }}</div>
        @enderror   

        <h2 class="hello-message">كود التحقق</h2>
        <p class="intro">الرجاء ادخال كود التحقق الذي وصلك</p>

        <div class="form-group">
            <label for="verification_code">كود التحقق *</label>
            <div class="input-group">
                <input type="text" id="verification_code" placeholder="الرجاء ادخل كود التحقق" required
                    name="verification_code" />
                <img src="{{ asset('website/asset/images/verify-code-icon.svg') }}" alt="Verify Code Icon"
                    class="input-icon" />
            </div>
        </div>

        <button type="submit" class="submit-btn">تحقق</button>
    </form>

@endsection
