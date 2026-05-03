<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>عقاراتي</title>
    <link rel="icon" href="{{ asset('website/asset/images/logo.png')}}" type="image/svg+xml" />
    <link rel="stylesheet" href="{{ asset('website/asset/css/style.css')}}" />
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="{{ asset('website/asset/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.1/moment.min.js"></script>
    <script src="{{ asset('website/asset/js/bootstrap-hijri-datetimepickermin.js') }}"></script>

  </head>
  <body>
    <section class="real-state-form">
     <nav class="nav-menu-state">
  <img src="{{ asset('website/asset/images/decorative-form.svg') }}" alt="Decorative Form Image" class="decorative-form" />
  
  <!-- Step 1: عقدي -->
  <a href="{{ url('realEstate') }}" 
     class="nav-link-state contract-link {{ request()->is('realEstate') ? 'active-state' : '' }}">
      عقدي
      <img src="{{ asset('website/asset/images/aqdi-icon.svg') }}" alt="Aqdi Icon" />
  </a>
  
  <!-- Step 2: الصك -->
  <a href="" 
     class="nav-link-state contract-link {{ request()->routeIs('create.step1.realEstate') ? 'active-state' : '' }}">
      الصك
  </a>
  
  <!-- Step 3: المالك -->
  <a href="" 
     class="nav-link-state contract-link {{ request()->is('step2/realEstate/*') ? 'active-state' : '' }}">
      المالك
  </a>
  
  <!-- Step 4: المستأجر -->
  <a href="" 
     class="nav-link-state contract-link {{ request()->routeIs('createStep3.realEstate') ? 'active-state' : '' }}">
      المستأجر
  </a>
  
  <!-- Ejar Icon Link -->
  <a href="" 
     class="nav-link-state contract-link {{ request()->routeIs('create.step1.realEstate') ? 'active-state' : '' }}">
      <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="Ejar Icon" class="ejar" />
  </a>
</nav>

      @yield('content')


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
       src: url('{{ asset("website/asset/fonts/alfont_com_Montserrat-Arabic-ExtraBold-2.ttf") }}') format('truetype');
       font-weight: 800;
     } 
     
     </style>