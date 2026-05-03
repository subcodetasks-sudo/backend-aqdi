@extends('website._layout.app')

@section('title', 'استعادة كلمة المرور')

@section('content')

    <!-- Display any general error or success message -->
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <!-- Start of the form -->
    <form action="{{ route('website.postSendCode') }}" method="POST">
        @csrf
        <h2 class="hello-message">استعادة كلمة المرور</h2>
        <p class="intro">الرجاء إدخال رقم الجوال المسجل لدينا</p>

        <div class="form-group">
            <label for="phone">رقم الجوال *</label>
            <div class="input-group">
                <span class="country-code">+966</span>
                <input type="text" id="phone" placeholder="رقم الجوال" required minlength="6" maxlength="15"
                    name="mobile" value="{{ old('mobile') }}" />
                <img src="{{ asset('website/asset/images/phone-number-icon.svg') }}" alt="Phone Icon" class="input-icon" />
            </div>

            <!-- Display error message for mobile field -->
            @error('mobile')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit button inside the form -->
        <button type="submit" class="submit-btn">ارسال كود التحقق</button>

        <!-- Display any general error or success message -->
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @elseif (session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif
    </form>
@endsection
