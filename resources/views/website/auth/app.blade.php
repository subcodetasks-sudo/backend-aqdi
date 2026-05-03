    <?php
    
    $setting = App\Models\Setting::where('id', '1')->first();
    
    ?>
    
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <!-- Meta Title -->
        <title>عقد إيجار إلكتروني موثق خلال 30 دقيقة | عقدي</title>
        
        <!-- Meta Description -->
        <meta name="description" content="احصل على عقد إيجار إلكتروني موثق من شبكة إيجار خلال 30 دقيقة عبر موقع عقدي. خدمة سريعة وسهلة لتلبية جميع احتياجاتك في المملكة.">
        <meta name="google-site-verification" content="QUTDt7oN3URTv9kB7ffdaeeBw2CDkbet1eaQFeCJ1d4"/>
    
        <!-- 32x32 -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('website/asset/images/30-30.png') }}">
        <!-- 16x16 -->
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('website/asset/images/16-16.png') }}">
        <!-- Apple Touch Icon -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('website/asset/images/30-30.png') }}">



        <a href="{{ route('website.home') }}">
        <link rel="icon" href="{{ asset('website/asset/images/logo.png') }}" type="image/svg+xml" />
        <link rel="icon" href="{{ asset('website/asset/images/logo.png') }}" type="image/png" />
        </a>
        <!-- FontAwesome CDN Link -->
        <script src="https://kit.fontawesome.com/5ef60b71ad.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="{{ asset('website/asset/css/style.css') }}" />
            <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Organization",
          "name": "Aqdi",
          "url": "https://aqdi.sa/",
          "logo": "https://aqdi.sa/website/asset/images/logo.png",
        }
        </script>
    </head>
    <body>
    <div class="home-container">
        <!-- Header And Aside For the Login User -->
        <header class="header">
            <div class="logo">
              <a href="{{ route('website.home') }}">

                <img src="{{ asset('website/asset/images/logo.svg') }}" alt="Logo" />
               </a>
            </div>
            <nav class="nav-links">
                <a href="{{ route('website.home') }}" class="{{ request()->routeIs('website.home') ? 'active' : '' }}">الرئيسية</a>
                <a href="{{ route('realEstate') }}" class="{{ request()->routeIs('realEstate') ? 'active' : '' }}">عقاراتي</a>
                <a href="{{ route('myContract') }}" class="{{ request()->routeIs('myContract') ? 'active' : '' }}">الطلبات</a>
                <a href="{{ route('website.blog') }}" class="{{ request()->routeIs('website.blog') ? 'active' : '' }}">المدونة</a>
                <a href="{{ route('website.aboutUs') }}" class="{{ request()->routeIs('website.aboutUs') ? 'active' : '' }}">من نحن</a>
                <a href="{{ route('website.qa') }}" class="{{ request()->routeIs('website.qa') ? 'active' : '' }}">الاسئلة الشائعة</a>
            </nav>
            
            @if(Auth::user())
            
            <div class="user-box">
                <div id="toggleImage">
                <img src="{{ asset('website/asset/images/bottom-arrow-icon.svg') }}" alt=""  />
                </div>
                
                @if(Auth::user())
                <p>{{ Auth::user()->fname }}</p>

                @else
               <p></p>
                @endif
                <a href="{{ route('profile') }}">
                    <img src="{{ asset('website/asset/images/user-icon.svg') }}" alt="" />
                </a>

                <div class="user-options" id="user-options">
                    <a href="{{ route('profile') }}">
                        <span>المعلومات الشخصية</span>
                        <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" />
                    </a>
                    <a href="{{ route('myContract') }}">
                        <span>الطلبات</span>
                        <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" />
                    </a>
                    <a class="log-out" href="{{ route('website.logout') }}">
                        <span>تسجيل الخروج</span>
                        <img src="{{ asset('website/asset/images/small-left-arrow-red.svg') }}" alt="" />
                    </a>
                </div>
            <i class="fa-solid fa-bars-staggered hamburger" id="hamb"></i>
            </div>
            @else
            <a href="{{ route('website.login') }}" class="login">تسجيل الدخول</a>
            @endif
         </header>
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="{{ asset('website/asset/images/logo.svg') }}" alt="Logo" />
                </div>
                <i class="fa-solid fa-circle-xmark close-btn"></i>
            </div>
            <nav class="sidebar-nav">
                <a class="user-box-aside">
                    <div>
                        <i class="fa-solid fa-user"></i>
                       @if(Auth::user())

                        <span>{{ Auth::user()->fname }} .</span>
                       @else
                       <span></span>

                        @endif
                    </div>
                    <div id="toggleImageAside">
                    <img src="{{ asset('website/asset/images/bottom-arrow-icon.svg') }}" />
                    </div>
                    <div class="user-options-aside" id="user-options-aside">
                        <a href="#">
                            <span>المعلومات الشخصية</span>
                            <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" />
                        </a>
                        <a href="{{route('myContract')}}">
                            <span>الطلبات</span>
                            <img src="{{ asset('website/asset/images/small-left-arrow-grey.svg') }}" alt="" />
                        </a>
                    </div>
                </a>
                <a href="{{ route('website.home') }}" class="{{ request()->routeIs('website.home') ? 'active-state' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    <span>الرئيسية</span>
                </a>
                
                <a href="{{ route('realEstate') }}" class="{{ request()->routeIs('realEstate') ? 'active-state' : '' }}">
                    <i class="fa-solid fa-building-user"></i>
                    <span>عقاراتي</span>
                </a>
                
                <a href="{{ route('myContract') }}" class="{{ request()->routeIs('myContract') ? 'active-state' : '' }}">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>الطلبات</span>
                </a>
                
                <a href="{{ route('website.blog') }}">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>المدونة</span>
                </a>
                
                <a href="{{ route('website.aboutUs') }}" class="{{ request()->routeIs('website.aboutUs') ? 'active-state' : '' }}">
                    <i class="fa-solid fa-people-group"></i>
                    <span>من نحن</span>
                </a>
                
                <a href="{{ route('website.qa') }}" class="{{ request()->routeIs('website.qa') ? 'active-state' : '' }}">
                    <i class="fa-solid fa-question"></i>
                    <span>الاسئلة الشائعة</span>
                </a>
                
                <a class="log-out" href="{{ route('website.logout') }}">
                    <i class="fa-solid fa-arrow-left-long"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </nav>
        </aside>

        @yield('content')

        <footer class="site-footer">
            <div class="footer-content">
                <div class="footer-logo-section">
                    <img src="{{ asset('website/asset/images/logo.svg') }}" alt="Logo" class="footer-logo" />
                    <p class="footer-description">
                        منشأة تجارية رسمية مسجلة في وزارة التجارة السعودية برقم  4650258662 وحاصلة على شهادة (وسيط عقاري معتمد) من منصة ايجار برقم00237930, والمركز السعودي للاعمال برقم 0000018828 ,  والهيئة العامة للعقار برقم   1200019246 تأسست تماشيا مع توجهات الدولة ورؤية 2030 في تعزيز التجارة الإلكترونية والعمل عن بعد.ماعي أو حافز أو لأي أغراض اخرى.
                    </p>
                </div>
                 <div class="footer-services">
                    <h4 class="footer-heading">الخدمات</h4>
                    <ul class="footer-nav">
                        <li><a href="{{ route('website.home') }}">توثيق عقد سكني</a></li>
                        <li><a href="{{ route('website.home') }}">توثيق عقد تجاري</a></li>
                        <li><a href="{{ route('myContract') }}">العقود</a></li>
                        <li><a href="{{ route('website.blog') }}">المدونة</a></li>
                    </ul>
                </div>
                <div class="footer-features">
                    <h4 class="footer-heading">مميزاتنا</h4>
                    <ul class="footer-nav">
                        <li><a href="#">ثقة عالية</a></li>
                        <li><a href="#">توفير وقتك</a></li>
                        <li><a href="#">دعم قوي 24/7</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4 class="footer-heading">تواصل معنا</h4>
                    <ul class="footer-contact-info">
                        <li>
                            <a href="https://wa.me/966597500014" target="_blank" rel="noopener noreferrer">
                                <img src="{{ asset('website/asset/images/whatsapp-footer-icon.svg') }}" alt="WhatsApp" />
                                {{ $setting->whatsapp ??''}}
                            </a>
                        </li>
                        <li>
                            <a href="mailto:info@aqdi.sa">
                                <i class="fas fa-envelope"></i> <!-- Font Awesome email icon -->
                                info@aqdi.sa
                            </a>

                        </li>
                    </ul>
                </div>
            </div>
            
               <div id="app">
                       <a href="https://wa.me/966597500014" class="whatsapp-icon" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.001-3.825 3.113-6.937 6.937-6.937 1.856.001 3.598.723 4.907 2.034 1.31 1.311 2.031 3.054 2.03 4.908-.001 3.825-3.113 6.938-6.937 6.938z"/>
                       </svg>
                </a>
            </div>
            
            <div class="footer-social-media">
                <h4 class="footer-heading">تابعونا على</h4>
                <ul class="social-icons">
                    <li>
                        <a href="https://wa.me/966597500014" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('website/asset/images/whatsapp-footer-icon.svg') }}" alt="WhatsApp" />
                        </a>
                    </li>
                    <li>
                        <a href="https://snapchat.com/t/EZYqNCQY"  class="fa fa-snapchat"  style="color:rgb(4, 4, 4)" target="_blank" rel="noopener noreferrer">
                         </a>
                    </li>
                    <li>
                        <a href="https://www.tiktok.com/{{ $setting->tiktok ??'' }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('website/asset/images/tiktok-footer-icon.svg') }}" alt="TikTok" />
                        </a>
                    </li>
                    <li>
                        <a href="https://x.com/aqdi_sa" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('website/asset/images/x-footer-icon.svg') }}" alt="X (Twitter)" />
                        </a>
                    </li>
                </ul>
            </div>
        </footer>
        
          <section class="footer-bottom">
           <div class="footer-bottom-links">
        <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener noreferrer"></a>
        <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener noreferrer"></a>
        </div>
            <p>سياسة الخصوصية </p>
            <p>جميع الحقوق محفوظة عقدي 2024 ©</p>
          <a href="{{route('website.terms')}}"> الشروط والاحكام</a>
          </section>
       </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelector("#hamb").addEventListener("click", function () {
                document.querySelector(".sidebar").classList.add("open");
            });

            document.querySelector(".close-btn").addEventListener("click", function () {
                document.querySelector(".sidebar").classList.remove("open");
            });

            // Function to toggle the view of User Options
            document.getElementById("toggleImage").addEventListener("click", function () {
                let userOptions = document.getElementById("user-options");
                userOptions.style.display = (userOptions.style.display === "block") ? "none" : "block";
            });

            // Function to toggle the view of User Options in the sidebar
            document.getElementById("toggleImageAside").addEventListener("click", function () {
                let userOptionsAside = document.getElementById("user-options-aside");
                userOptionsAside.style.display = (userOptionsAside.style.display === "block") ? "none" : "block";
            });

            // Toggle password visibility
            window.togglePassword = function () {
                const passwordInput = document.getElementById("password");
                const toggleIcon = document.querySelector(".toggle-icon");
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    toggleIcon.classList.add("fa-eye-slash");
                    toggleIcon.classList.remove("fa-eye");
                } else {
                    passwordInput.type = "password";
                    toggleIcon.classList.add("fa-eye");
                    toggleIcon.classList.remove("fa-eye-slash");
                }
            };
        });
    </script>
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
   
   
.whatsapp-icon {
  position: fixed;
  bottom: 20px;
  left: 20px;
  background-color: #25D366;
  color: white;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
  transition: all 0.3s ease;
  z-index: 1000;
}

.whatsapp-icon:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
}

.whatsapp-icon svg {
  width: 35px;
  height: 35px;
}

   </style>