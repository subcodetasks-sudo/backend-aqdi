@extends('website.auth.app')

@section('title', 'معلومات الحساب')

@section('content')

<nav class="breadcrumb">
    <a href="{{ url('/') }}">الرئيسيه</a>
    <a href="{{ url('profile') }}">معلومات الحساب</a>
</nav>

<!-- Personal Information Form Section -->
<section class="personal-info">
    <div class="profile-image">
      <img src="{{ asset($user->getPhotoPathAttribute()) }}" alt="Camera Icon" id="photo-preview"/>
      <form action="{{ route('update.profile.photo') }}" method="POST" enctype="multipart/form-data">
            @csrf
             <input type="file" id="profilePicInput" name="photo" accept="image/*" style="display: none" onchange="updateProfilePhoto()"/>
            <script>
                function updateProfilePhoto() {
                    var input = document.getElementById('profilePicInput');
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('photo-preview').src = e.target.result;
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
            </script>

            <label for="profilePicInput" class="camera-icon">
                <img src="{{ asset($user->getPhotoPathAttribute()) }}" alt="Camera Icon" id="photo-preview"/>
            </label>
        </div>
        <button type="submit" class="next-button-state btn btn-primary" style="font-size: 18px;">تغيير الصورة</button>
        
        </form>

        <br><br>
        <h2 class="user-name">{{ $user->fname }}</h2>

        <!-- Form Section -->
        <form action="{{ route('update.profile', auth()->id()) }}" method="POST">
          @method('PUT')
          @csrf
          <div class="form-group">
              <label for="fname">الاسم بالكامل *</label>
              <div class="input-group">
                  <input type="text" id="fname" placeholder="الاسم بالكامل" value="{{ old('fname', $user->fname) }}" name="fname" minlength="3" maxlength="100" />
                  <img src="{{ asset('website/asset/images/name-icon.svg') }}" alt="" class="input-icon" />
              </div>
          </div>
      
          <div class="form-group">
            <label for="mobile">رقم الجوال *</label>
            <div class="input-group">
                <span class="country-code">+966</span>
                <input type="text" id="mobile" name="mobile"
                       placeholder="رقم الجوال المبدوء بـ 5 ويتكون من 9 أرقام"
                       value="{{ old('mobile', $mobile) }}" minlength="9" maxlength="9" />
                <img src="{{ asset('website/asset/images/phone-number-icon.svg') }}" alt="" class="input-icon" />
            </div>
            <span id="phone-error" style="color: red; display: none;"></span>
        </div>
        
          <div class="form-group">
              <label for="email">البريد الالكتروني *</label>
              <div class="input-group">
                  <input type="email" id="email" name="email" placeholder="البريد الالكتروني" value="{{ old('email', $user->email) }}" minlength="13" maxlength="70" />
                  <img src="{{ asset('website/asset/images/email-icon.svg') }}" alt="" class="input-icon" />
              </div>
          </div>
      
          <div class="form-group">
              <label for="password">كلمة المرور *</label>
              <div class="input-group">
                  <input type="password" id="password" placeholder="كلمة المرور" name="password" minlength="8" maxlength="30" autocomplete="new-password" />
                  <img src="{{ asset('website/asset/images/password-icon.svg') }}" alt="" class="input-icon" />
                  <img src="{{ asset('website/asset/images/eye-icon.svg') }}" alt="" class="toggle-icon" onclick="togglePassword()" />
              </div>
          </div>
      
          <div class="buttons-state">
              <button type="button" class="back-button-state" onclick="this.form.reset()">عوده</button>
              <button type="submit" class="next-button-state">حفظ <i class="fa-solid fa-arrow-left"></i></button>
          </div>
      </form>
</section>      
<script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById("password");
        const toggleIcon = document.querySelector(".toggle-icon");
        const showIcon = "{{ asset('website/asset/images/eye-dashed-icon.svg') }}";
        const hideIcon = "{{ asset('website/asset/images/eye-icon.svg') }}";

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.src = showIcon;
        } else {
            passwordInput.type = "password";
            toggleIcon.src = hideIcon;
        }
    }

    // Update profile photo preview
    function updateProfilePhoto() {
        const input = document.getElementById('profilePicInput');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('photo-preview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Validate phone number
    function validatePhoneNumber() {
    const mobileInput = document.getElementById('mobile');
    const phoneError = document.getElementById('phone-error');
    const mobileValue = mobileInput.value.trim();

    // Validate length and pattern
    if (!mobileValue.match(/^5[0-9]{8}$/)) {
        phoneError.textContent = 'لابد أن يبدأ الرقم بـ 5 ويتكون من 9 أرقام.';
        phoneError.style.display = 'block';
        mobileInput.classList.add('error');
        return false;
    } else {
        phoneError.style.display = 'none';
        mobileInput.classList.remove('error');
        return true;
    }
}

// Attach validation on input change
document.getElementById('mobile').addEventListener('input', validatePhoneNumber);

// Final check on form submission
document.querySelector('form').addEventListener('submit', function (e) {
    if (!validatePhoneNumber()) {
        e.preventDefault(); // Prevent form submission if invalid
    }
});

</script>

@endsection


<style>
  .profile-image {
    width: 150px; /* Limit the size of the profile image container */
    height: 150px;
    border-radius: 50%; /* Make it circular */
    position: relative;
    background-color: #f3f3f3;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden; /* Ensure image doesn't overflow the container */
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensure the image fits inside the box */
    border-radius: 50%;
}

.profile-image .initials {
    position: absolute;
    font-size: 2rem;
    color: #fff;
    text-transform: uppercase;
    background-color: #aaa;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
}

.camera-icon {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background-color: #fff;
    padding: 5px;
    border-radius: 50%;
    cursor: pointer;
}

input[type="file"] {
    display: none;
}

</style>