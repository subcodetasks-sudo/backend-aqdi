@extends('website._layout.app')

@section('title', ' انشاء حساب')

    <!-- FontAwesome CDN Link -->
    <script
      src="https://kit.fontawesome.com/5ef60b71ad.js"
      crossorigin="anonymous"
    ></script>

@section('meta')
    <meta name="title" content="عقدي - انشاء حساب">
    <meta name="description" content="انشاء حساب">
@endsection

@section('content')
    <div class="login-container">

        @if (session('success'))
            <div id="successModal" class="modal">
                <div class="modal-content">
                    <img src="{{ asset('website/asset/images/success-icon.svg') }}" alt="Success Icon" class="modal-icon" />
                    <h2 class="modal-message">{{ session('success') }}</h2>
                    <a href="{{ route('website.login') }}" class="modal-btn">تسجيل الدخول</a>
                </div>
            </div>
        @endif

        <form action="{{ route('website.PostSignup') }}" method="POST" id="signupForm">
            @csrf
            <div class="links">
                <a href="{{ route('website.login') }}">تسجيل الدخول</a>
                <a href="{{ route('website.signup') }}" class="active">إنشاء حساب</a>
            </div>

            <h2 class="hello-message">مرحبا 👋</h2>
            <p class="intro">ادخل البيانات لانشاء الحساب</p>

            <div class="form-group">
                <label for="fullName">الاسم بالكامل *</label>
                <div class="input-group">
                    <input type="text" id="fullName" name="fname" value="{{ old('fname') }}" placeholder="الاسم بالكامل" />
                    <img src="{{ asset('website/asset/images/name-icon.svg') }}" alt="Name Icon" class="input-icon" />
                </div>
                @error('fname')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

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
                
                <img src="{{ asset('website/asset/images/phone-number-icon.svg') }}" alt="Phone Icon"
                        class="input-icon" />
                </div>
                @error('mobile')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for= "email">البريد الالكتروني</label>
                <div class="input-group">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="البريد الالكتروني"
                        autocomplete="email" />
                    <img src="{{ asset('website/asset/images/email-icon.svg') }}" alt="Email Icon" class="input-icon" />
                </div>
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور *</label>
                <div class="input-group">
                    <input type="password" id="password" placeholder="كلمة المرور" value="{{ old('password') }}" required minlength="8" name="password"
                        maxlength="30" autocomplete="new-password" />
                    <img src="{{ asset('website/asset/images/password-icon.svg')}}" alt="" class="input-icon" />
                    <i class="fa-regular fa-eye toggle-icon" onclick="togglePassword()"></i>
                </div>
            </div>
         
            <div class="form-group">
                <label for="confirmPassword">تأكيد كلمة المرور *</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation" id="confirmPassword" value="{{ old('password_confirmation') }}" placeholder="تأكيد كلمة المرور"
                        required minlength="8" maxlength="30" autocomplete="new-password" />
                    <img src="{{ asset('website/asset/images/password-icon.svg')}}" alt="" class="input-icon" />
                    <i class="fa-regular fa-eye toggle-icon" onclick="toggleConfirmPassword()"></i>
                </div>
            </div>
           
            @error('password')
            <div class="error-message">{{ $message }}</div>
        @enderror
            <button type="submit" class="submit-btn">انشاء حساب</button>
        </form>

        <br><br><br>
        
        

<!-- Success Modal -->
      <div id="successModal" class="modal-signup">
        <div class="modal-content">
          <img
            src="{{ asset('website/asset/images/sucess-icon.svg')}}"
            alt="Success Icon"
            class="modal-icon"
          />
          <h2 class="modal-message">تم انشاء حسابكم بنجاح</h2>
           <a href="{{ route('website.verification') }}" class="modal-btn">تأكيد الحساب</a>
        </div>
      </div>

    </div>

    <script src="{{ asset('website/asset/js/index.js') }}"></script>
    <script>
        // Signup Success Modal


       function togglePassword() {
        const passwordInput = document.getElementById("password");
        const toggleIcon = passwordInput.nextElementSibling.nextElementSibling;

        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          toggleIcon.classList.remove("fa-eye");
          toggleIcon.classList.add("fa-eye-slash");
        } else {
          passwordInput.type = "password";
          toggleIcon.classList.remove("fa-eye-slash");
          toggleIcon.classList.add("fa-eye");
        }
      }

      function toggleConfirmPassword() {
        const confirmPasswordInput = document.getElementById("confirmPassword");
        const toggleIcon =
          confirmPasswordInput.nextElementSibling.nextElementSibling;

        if (confirmPasswordInput.type === "password") {
          confirmPasswordInput.type = "text";
          toggleIcon.classList.remove("fa-eye");
          toggleIcon.classList.add("fa-eye-slash");
        } else {
          confirmPasswordInput.type = "password";
          toggleIcon.classList.remove("fa-eye-slash");
          toggleIcon.classList.add("fa-eye");
        }
      }
      
      
    const submitButton = document.querySelector('.submit-btn');
    const successModal = document.getElementById('successModal');

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

      
