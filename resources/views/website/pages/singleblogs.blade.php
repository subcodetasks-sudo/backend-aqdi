@extends('website.auth.app')

@section('title', $blog->title)


@section('meta')
<meta name="title" content="{{ $blog->meta_title ?? $blog->title }}">
<meta name="description" content="{{ $blog->meta_description ?? Str::limit(strip_tags($blog->description), 160) }}">
@endsection


@section('content')
<!-- Breadcrumb Navigation -->
<nav class="breadcrumb">
    <a href="{{ route('website.home') }}">الرئيسيه</a>
    <a href="{{ route('website.blog') }}">المدونه</a>
<a href="{{ route('website.singelblog', ['slug' => $blog->slug ?? '#']) }}">{{ $blog->title }}</a>
</nav>


<!-- About Blog-id Section -->
<section class="blog-id-about">
    <div class="blog-id-content">
        <h3 class="blog-id-header">المدونه</h3>
        <h2 class="blog-id-title">{{ $blog->title }}</h2>
    </div>
</section>

<!-- Specific Blog Section -->
<div class="specfic-blog">
    <div class="image-specfic-blog">
        <img src="{{ asset('storage/'.$blog->image) }}" alt="" />
    </div>
    <p class="blog-data-specfic">
        <i class="fas fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($blog->created_at)->translatedFormat('l, d F Y') }}
    </p>
    <div class="text-content-specfic">
        <p>
            {!! $blog->description	 !!}
        </p>
    </div>
</div>

<!-- Our Blogs Section -->
<div class="blogs-section">
  @foreach($relatedBlogs as $related)
  <div class="blog-wrapper">
      <a href="{{ route('website.singelblog', $related->slug) }}" class="blog-item">
          <div class="image-blog">
              <img style= "height: 257px; border-radius: 24px" src="{{ asset('storage/'.$related->image) }}" alt="{{ $related->title }}" />
          </div>
          <p class="blog-data">
              <i class="fas fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($related->created_at)->translatedFormat('l, d F Y') }}
          </p>
          <h3 class="blog-heading">{{ $related->title }}</h3>
          <p class="blog-text-content">{{ Str::limit(strip_tags($blog->description), 150) }}</p>
          
      </a>
  </div>
  @endforeach
</div>

<section class="contact-section">
    <div class="container">
        <h2>للاستفسار عن توثيق العقود<br />على الواتساب</h2>
        <p>يمكنك التواصل مع فريق “ عقدي “ بشكل مباشر في أي وقت</p>
        <a href="https://wa.me/{{ urlencode($setting->whatsapp ?? '') }}" class="contact-button">
            تواصل معنا
            <img src="{{ asset('website/asset/images/whatsapp-icon.svg') }}" alt="WhatsApp Icon" />
        </a>
    </div>
</section>

<section class="app-download-section">
    <div class="app-download-info">
        <h2 class="app-download-heading">
            <img src="{{ asset('website/asset/images/star-icon.svg') }}" alt="Star Icon" class="app-download-star-icon" />
            قم بتحميل تطبيقــــــــــــنا
        </h2>
        <p class="app-download-description">
            مهما كان غرضك من العقد ، نحن خيارك الأكثر سرعة ومن أي مكان في المملكة ، سواء كنت تحتاجه لحساب المواطن أو الضمان الاجتماعي أو حافز أو لأي أغراض اخرى.
        </p>
        <div class="app-download-buttons">
            <a href="#">
                <img src="{{ asset('website/asset/images/google.svg') }}" alt="Google Play" />
            </a>
            <a href="#">
                <img src="{{ asset('website/asset/images/apple.svg') }}" alt="App Store" />
            </a>
        </div>
    </div>
    <!--<div class="app-download-image">-->
    <!--    <img src="{{ asset('website/asset/images/blog-download-app.png') }}" alt="App Image" />-->
    <!--</div>-->
</section>

@endsection

