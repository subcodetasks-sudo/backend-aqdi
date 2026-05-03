@extends('website._layout.app')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <form action="{{ route('website.ResetPassword') }}" method="POST" id="newPasswordForm">
        @csrf
        <div class="links">
            <a href="{{ url('index') }}">تسجيل الدخول</a>
            <a href="{{ url('signup') }}">إنشاء حساب</a>
        </div>

        <h2 class="hello-message">كلمة المرور الجديدة</h2>
        <p class="intro">الرجاء إدخال كلمة مرور جديدة</p>

        <!-- Error message handling -->
        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Success message handling (trigger modal if password reset is successful) -->
        @if (session('success'))
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    showModal();
                });
            </script>
        @endif
        <input type="hidden"  class="form-control" name="mobile" id="mobile" value="{{ $user->mobile }}" >

        <div class="form-group">
            <label for="new-password">كلمة المرور الجديدة *</label>
            <div class="input-group">
                <input type="password" id="new-password" placeholder="كلمة المرور الجديدة" required minlength="8"
                    name="password" maxlength="30" autocomplete="new-password" />
                <img src="{{ asset('website/asset/images/password-icon.svg') }}" alt="" class="input-icon" />
                <img src="{{ asset('website/asset/images/eye-icon.svg') }}" alt=""
                    class="toggle-icon new-password-toggle"
                    onclick="togglePassword('new-password', '.new-password-toggle')" />
            </div>
        </div>

        <div class="form-group">
            <label for="confirm-new-password">تأكيد كلمة المرور الجديدة *</label>
            <div class="input-group">
                <input type="password" name="password_confirmation" id="confirm-new-password" placeholder="تأكيد كلمة المرور" required
                    minlength="8" maxlength="30" autocomplete="new-password" />
                <img src="{{ asset('website/asset/images/password-icon.svg') }}" alt="" class="input-icon" />
                <img src="{{ asset('website/asset/images/eye-icon.svg') }}" alt=""
                    class="toggle-icon confirm-password-toggle"
                    onclick="togglePassword('confirm-new-password', '.confirm-password-toggle')" />
            </div>
        </div>

        <button type="submit" class="submit-btn">تأكيد</button>
    </form>

    <!-- Success Modal -->
    <div id="successModal" class="modal" style="display: none;">
        <div class="modal-content">
            <img src="{{ asset('website/asset/images/success-icon.svg') }}" alt="Success Icon" class="modal-icon" />
            <h2 class="modal-message">تم تعديل كلمة المرور بنجاح</h2>
            <a href="{{ url('website.home') }}" class="modal-btn">تسجيل الدخول</a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function togglePassword(inputId, iconSelector) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.querySelector(iconSelector);

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.src = "{{ asset('website/asset/images/eye-dashed-icon.svg') }}";
            } else {
                passwordInput.type = "password";
                toggleIcon.src = "{{ asset('website/asset/images/eye-icon.svg') }}";
            }
        }

        function showModal() {
            const successModal = document.getElementById("successModal");
            successModal.style.display = "flex";
        }

        @if(session('success'))
            showModal();
        @endif
    </script>
@endsection
