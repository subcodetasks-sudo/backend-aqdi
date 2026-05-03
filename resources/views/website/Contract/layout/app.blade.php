<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="google-site-verification" content="QUTDt7oN3URTv9kB7ffdaeeBw2CDkbet1eaQFeCJ1d4"/>

  <title>عقدي</title>
  
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="{{ asset('website/asset/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet" />
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.1/moment.min.js"></script>
  <script src="{{ asset('website/asset/js/bootstrap-hijri-datetimepickermin.js') }}"></script>
  
   <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('website/asset/images/logo.png') }}">
 
  <link rel="stylesheet" href="{{ asset('website/asset/css/style.css') }}" />
  
</head>
<body>
 <section class="real-state-form">
    <nav class="nav-menu-state">
        <img src="{{ asset('website/asset/images/decorative-form.svg') }}" alt="Decorative Form Image" class="decorative-form" />

        @if($contract->real_id == null)
            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('step1') ? 'active-state' : '' }}">
                عقدي
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('step2') ? 'active-state' : '' }}">
                الصك
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('contract.step3') ? 'active-state' : '' }}">
                المالك
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('contract.step4') ? 'active-state' : '' }}">
                المستأجر
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('Financial') ? 'active-state' : '' }}">
                البيانات المالية
            </a>

            <a href="#" class="nav-link-state contract-link">
                <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="Ejar Icon" class="ejar" />
            </a>
        @else
            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('step1') ? 'active-state' : '' }}">
                عقدي
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('step2') ? 'active-state' : '' }}">
                الصك
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('contract.step3') ? 'active-state' : '' }}">
                المالك
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('contract.step4') ? 'active-state' : '' }}">
                المستأجر
            </a>

            <a href="#" 
               class="nav-link-state contract-link {{ request()->routeIs('Financial') ? 'active-state' : '' }}">
                البيانات المالية
            </a>

            <a href="#" class="nav-link-state contract-link">
                <img src="{{ asset('website/asset/images/ejar-icon.svg') }}" alt="Ejar Icon" class="ejar" />
            </a>
        @endif
    </nav>
 
    @yield('content')
</section>

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

 <!-- Script for date picker functionality -->
  <script>
      $(function () {
          // Initialize date picker with Hijri by default
          initDatePicker("hijri");

          // Event listener for calendar switcher
          $("#calendar-switcher").on("change", function () {
              const selectedCalendar = $(this).val();
              initDatePicker(selectedCalendar);
          });
      });

      function initDatePicker(calendarType) {
          // Define options for the date picker
          const options = {
              hijri: calendarType === "hijri",
              showSwitcher: false
          };

          // Destroy the previous instance if it exists and initialize a new one
          $(".date-picker").hijriDatePicker("destroy").hijriDatePicker(options);
      }
  </script>