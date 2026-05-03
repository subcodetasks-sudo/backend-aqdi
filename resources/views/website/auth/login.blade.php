@extends('website._layout.app')

@section('title', 'تسجيل الدخول')
@section('content')
 
 @section('meta')
    <meta name="title" content="عقدي - تسجيل الدخول">
    <meta name="description" content="تسجيل الدخول">
@endsection
    <form id="loginForm" action="{{ route('website.login') }}" method="POST">
        @csrf
        <div class="links">
            <a href="{{ route('website.login') }}" class="active">تسجيل الدخول</a>
            <a href="{{ route('website.signup') }}">إنشاء حساب</a>
        </div>

        <h2 class="hello-message">مرحبا 👋</h2>
        <p class="intro">ادخل رقم الجوال و كلمة المرور لتسجيل الدخول</p>
        <div class="form-group">
            <label for="phone">رقم الجوال *</label>
            <div class="input-group">
                <span class="country-code">+966</span>
                <input
                    type="text"
                    id="phone"
                    maxlength="9"
                    placeholder="رقم الجوال المبدوء بـ 5 ويتكون من 9 أرقام"
                    name="mobile"
                    value="{{ old('mobile') }}"
                    onchange="validatePhoneNumber()"
                />
            </div>
            
            <img
                src="{{ asset('website/asset/images/phone-number-icon.svg') }}"
                alt=""
                class="input-icon"
            />
            @error('mobile')
            <div class="error-message" style="border: 1px solid red; padding: 8px; border-radius: 5px; margin-top: 5px;">
                {{ $message }}
            </div>
            @enderror
            <span id="phone-error" class="error-message" style="color: red; display: none;"></span>
        </div>

        <div class="form-group">
            <label for="password">كلمة المرور *</label>
            <div class="input-group">
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="كلمة المرور"
                    autocomplete="new-password"
                />
                <img src="{{ asset('website/asset/images/password-icon.svg') }}" alt="" class="input-icon" />
                <img
                    src="{{ asset('website/asset/images/eye-icon.svg') }}"
                    alt=""
                    class="toggle-icon"
                    onclick="togglePassword()"
                />
            </div>
            @error('password')
            <div class="error-message" style="border: 1px solid red; padding: 8px; border-radius: 5px; margin-top: 5px;">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="options">
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" />
                <label for="remember">تذكرني</label>
            </div>
            <a href="{{ route('website.forgotPassword') }}" class="forgot-password">نسيت كلمة السر ؟</a>
        </div>

        <button type="submit" class="submit-btn">تسجيل الدخول</button>
    </form>
    
    <!-- Success Modal -->
    <div id="successModal" class="modal-signup">
        <div class="modal-content">
            <img
                src="{{ asset('website/asset/images/sucess-icon.svg')}}"
                alt="Success Icon"
                class="modal-icon"
            />
            <h2 class="modal-message">تسجيل الدخول بنجاح</h2>
            <a href="{{ route('website.login') }}" class="modal-btn">الرئيسية</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const toggleIcon = document.querySelector(".toggle-icon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.src = "{{ asset('website/asset/images/eye-dashed-icon.svg') }}";
            } else {
                passwordInput.type = "password";
                toggleIcon.src = "{{ asset('website/asset/images/eye-icon.svg') }}";
            }
        }

        function validatePhoneNumber() {
            const phoneInput = document.getElementById('phone');
            const phoneValue = phoneInput.value;
            const phoneError = document.getElementById('phone-error');
    
            // Check if the phone number starts with '5' and contains 10 digits
            if (phoneValue && !phoneValue.startsWith('5')) {
                phoneError.textContent = 'لابد أن يبدأ الرقم بـ 5';
                phoneError.style.display = 'block';
                phoneInput.classList.add('error');  // Optional, to add a red border to the input
            } else {
                phoneError.style.display = 'none';
                phoneInput.classList.remove('error');  // Remove red border if valid
            }
        }
    </script>
@endsection
