@extends('website.auth.app')

@section('title', 'المدونه')

@section('content')

    <!-- Meta Title and Meta Description --> 
    @section('meta')
        <meta name="title" content="عقدي - المدونة">
        <meta name="description" content="عقدي هو منصة إلكترونية متكاملة تقدم حلولًا مبتكرة لتنظيم وتطوير قطاع الإيجار العقاري بالتعاون مع برنامج إيجار والهيئة العامة للعقار.">
    @endsection
    
    
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb">
        <a href="{{ route('website.home') }}">الرئيسيه</a>
        <a href="{{ route('website.blog') }}">المدونه</a>
        <a href="{{ route('website.singelblog', ['slug' => $currentBlog->slug ?? '#']) }}">توثيق ما يقارب 60 ألف عقد إيجار تجاري</a> <!-- Use dynamic route or static link -->
    </nav>
    
    <!-- About Blog-id Section -->
    <section class="blog-id-about">
        <div class="blog-id-content">
            <h3 class="blog-id-header">المدونه</h3>
            <h2 class="blog-id-title">توثيق ما يقارب 60 ألف عقد إيجار تجاري</h2>
        </div>
    </section>
    
    
    <!-- Our Blogs Section -->
    <div class="blogs-section">
        @foreach($blogs as $blog)
        <div class="blog-wrapper">
            <a href="{{ route('website.singelblog', ['slug' => $blog->slug  ?? '#']) }}" class="blog-item">
                <div class="image-blog">
                    <img style= "height: 257px; border-radius: 24px" src="{{ Storage::url($blog->image) }}" alt="{{ $blog->title }}" />
                </div>
                <p class="blog-data">
                    <i class="fas fa-calendar-alt"></i> {{ $blog->created_at->translatedFormat('l, d F Y') }}
                </p>
                <h3 class="blog-heading">{{ $blog->title }}</h3>
                <p class="blog-text-content">{{ Str::limit(strip_tags($blog->description), 150) }}</p>
    
                <div class="read-more">
                    <span>إقرأ المزيـــــد</span>
                    <i class="fa-solid fa-arrow-left-long"></i>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <h2>للاستفسار عن توثيق العقود<br />على الواتساب</h2>
            <p>يمكنك التواصل مع فريق “ عقدي “ بشكل مباشر في أي وقت</p>
            <a href="https://wa.me/+966597500014" class="contact-button">
                تواصل معنا
                <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp Icon" />
            </a>
        </div>
    </section>
    
    <!-- App Download Section -->
    <section class="app-download-section">
        <div class="app-download-info">
            <h2 class="app-download-heading">قم بتحميل تطبيقــــــــــــنا</h2>
            <p class="app-download-description">
                مهما كان غرضك من العقد ، نحن خيارك الأكثر سرعة ومن أي مكان في المملكة...
            </p>
           <div class="app-download-buttons">
                <a href="https://play.google.com/store/apps/details?id=com.alaqed.alaqed">
    
                    <img src="{{ asset('website/asset/images/google.svg') }}" alt="Google Play" />
                </a>
                <a href="https://apps.apple.com/us/app/aqdi/id6670163340">
    
                    <img src="{{ asset('website/asset/images/apple.svg') }}" alt="App Store" />
                </a>
            </div>
        </div>
        <!--<div class="app-download-image">-->
        <!--    <img src="{{ asset('website/asset/images/download-section.png') }}" alt="App Image" />-->
        <!--</div>-->
    </section>

@endsection
