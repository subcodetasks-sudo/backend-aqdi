<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'عقدي')</title>
    
     <!-- 32x32 -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('website/asset/images/30-30.png') }}">
    <!-- 16x16 -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('website/asset/images/30-30.png') }}">
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('website/asset/images/favicon.jpg') }}">


     <link rel="stylesheet" href="{{ asset('website/asset/css/style.css') }}" />
      <meta name="google-site-verification" content="QUTDt7oN3URTv9kB7ffdaeeBw2CDkbet1eaQFeCJ1d4"/>
</head>
<body>
        <div class="login-container">
            <!-- Corrected asset path -->
        <img src="{{ asset('website/asset/images/ellipse-top-left.svg') }}" alt="Decorative Ellipse" class="ellipse-top-left" />
        
        <a href="{{route('website.home')}}" class="logo">
            <img src="{{ asset('website/asset/images/logo.svg') }}" alt="Website Logo" />
        </a>

        <div class="content">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @elseif (session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')

                <div class="image-box">
                    <img src="{{ asset('website/asset/images/hero.png') }}" alt="Login Hero" />
                    <img src="{{ asset('website/asset/images/star-icon.svg') }}" alt="" class="star-icon" />
                     <img src="{{ asset('website/asset/images/ellipse-center.svg') }}" alt="" class="ellipse-center" />
                </div>
        </div>
    </div>
</body>
</html>
<style>
 
    @font-face {
     font-family: 'Montserrat Arabic';
     src: url('{{ asset("website/asset/fonts/alfont_com_Montserrat-Arabic-Light-2.ttf") }}') format('truetype');
    
     font-weight: 350;
   }
   
   @font-face {
     font-family: 'Montserrat Arabic';
     src: url('{{ asset("website/asset/fonts/alfont_com_Montserrat-Arabic-Regular-2.ttf") }}') format('truetype');
     font-weight: 400;
   }
   
   @font-face {
     font-family: 'Montserrat Arabic';
     src: url('{{ asset("website/asset/fonts/alfont_com_Montserrat-Arabic-Medium-1.ttf") }}') format('truetype');
     font-weight: 500;
   }
   
   @font-face {
     font-family: 'Montserrat Arabic';
     src: url('{{ asset("website/asset/fonts/alfont_com_Montserrat-Arabic-SemiBold-2.ttf") }}') format('truetype');
     font-weight: 600;
   } 
   @font-face {
     font-family: 'Montserrat Arabic';
     src: url('{{asset("website/asset/fonts/alfont_com_Montserrat-Arabic-Bold.ttf")}}') format('truetype');
     font-weight: 700;
   }
   
   @font-face {
     font-family: 'Montserrat Arabic';
     src: url('{{ asset("fonts/alfont_com_Montserrat-Arabic-ExtraBold-2.ttf") }}') format('truetype');
     font-weight: 800;
   } 
   
   </style>