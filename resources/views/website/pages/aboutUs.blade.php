@extends('website.auth.app')

@section('title', 'من نحن ')

@section('content') 
     <meta name="title" content="من نحن | عقدي - حلول إلكترونية مبتكرة لتطوير قطاع الإيجار العقاري">
    <meta name="description" content="اكتشف حلول عقدي الإلكترونية بالتعاون مع ايجار والهيئة العامة للعقار التي تسهم في تطوير وتنظيم قطاع الإيجار العقاري، وتعزيز الثقة فيه وتحفيز الاستثمار">
      <!-- Breadcrumb Navigation -->
      <nav class="breadcrumb"> 
        <a href="{{url('/')}}">الرئيسيه</a>
        <a href="{{url('about-us')}}">من نحن</a>
      </nav>

      <!-- About Section -->
      <section class="about-section">
        <div class="about-content"> 
          <h2 class="about-title">عن “ عقدي “</h2> 
          <p class="about-description">
          تطبيق وموقع عقدي تقدم مجموعة من الحلول الإلكترونية بالتعاون مع ايجار والهيئة العامة للعقار  التي تسهم في تطوير قطاع الإيجار العقاري وتنظيمه وتيسير أعماله، بما يحقق التوازن في القطاع وتعزيز الثقة به، ويسهم في تحفيز الاستثمار فيهه.
          </p>
        </div>
      </section>

      <!-- About Container -->
      <div class="about-container">
        <div class="about-box">
          <img
            src="{{ asset('website/asset/images/eye-icon.svg')}}"
            alt="Vision Image"
            class="about-image"/>

            <h3 class="box-about-header">الـــرؤية</h3>
          <p class="box-about-description">
          أن نصبح شركة رائدة في قطاع التقنيات العقارية وتحقيق مستهدفات رؤية 2030
          </p>
        </div>
        <div class="about-box">
          <img
            src="{{ asset('website/asset/images/message-icon-about.svg')}}"
            alt="Mission Image"
            class="about-image"
          />
          <h3 class="box-about-header">الـرســـالة</h3>
          <p class="box-about-description">
تقديم حلول مبتكرة وموثوقة تعزز الكفاءة والفعالية التشغيلية لعملائنا وتجعلنا في الوقت نفسه عنصرًا أساسيًّا في نجاح وسعادة شركائنا وموظفينا  
           </p>
        </div>
      </div>

      <section class="beneficiaries-section">
        <h2 class="beneficiaries-title">
          المستفيدون من (إيجار) للقطاع السكني أطراف العملية الإيجارية:
        </h2>
        <p class="beneficiaries-description">
          يُطلق على المشاركين في الإيجار العقاري السكني اسم "أطراف العملية"، وهم
          الجماعة المستهدفة للتسجيل في الشبكة الإلكترونية لخدمات الإيجار. يهدف
          ذلك لتنظيم القطاع وتوضيح التزاماتهم وواجباتهم من خلال عقد الإيجار
          الإلكتروني الموحّد، لحفظ حقوقهم.
        </p>
      </section>

      <!-- Benefits Section-->
      <div class="benefits-container">
        <div class="benefit-item">
          <img src="{{ asset('website/asset/images/whobuy.svg')}}" alt="المستأجر" class="benefit-image" />
          <h3 class="benefit-title">المستأجر</h3>
          <p class="benefit-description">الأفراد من المواطنين والمقيمين.</p>
        </div>
        <div class="benefit-item">
          <img src="{{ asset('website/asset/images/whosell.svg')}}" alt="المؤجر" class="benefit-image" />
          <h3 class="benefit-title">المؤجر</h3>
          <p class="benefit-description">
            المستثمرون في العقارات السكنية وملّاكها من الأفراد والمنشآت أو
            وكلائهم.
          </p>
        </div>
        <div class="benefit-item">
          <img
            src="{{ asset('website/asset/images/whomiddle.svg')}}"
            alt="الوسيط العقاري"
            class="benefit-image"
          />
          <h3 class="benefit-title">الوسيط العقاري</h3>
          <p class="benefit-description">
            مكاتب عقارات وشركات الوساطة العقارية       .
          </p>
        </div>
      </div>

            <!-- Goals Section -->
      <div class="goals-section">
        <h2 class="goals-header">
          يهدف ( إيجار ) إلى تنظيم قطاع الإيجار العقاري في المملكة العربية
          السعودية بصورة متوازنة تحفظ حقوق أطراف العملية الإيجارية، ومن أهدافه:
        </h2>
        <ol class="goals-list">
          <li class="goal-item">
            حفظ حقوق جميع أطراف العملية الإيجارية ( المستأجر، والمؤجر، والوسيط
            العقاري ) من خلال عقود إيجار إلكترونية موحّدة ومعتمدة من وزارة
            العدل.
          </li>
          <li class="goal-item">
            توثيق العقود وتسجيل بيانات الوحدات العقارية على شبكة ( إيجار )
            الإلكترونية، وتفعيل استخدامها كسندات تنفيذية.
          </li>
          <li class="goal-item">
            تقليص النزاعات المتصلة بقطاع الإيجار العقاري وتخفيف العبء على الجهات
            القضائية.
          </li>
          <li class="goal-item">
            رفع كفاءة قطاع الإيجار العقاري وتعزيز مساهمته في الناتج المحلي.
          </li>
          <li class="goal-item">
            تقليل مخاطر الاستثمار في الإيجار العقاري والتحفيز على الاستثمار فيه؛
            لتحقيق التوازن بين العرض والطلب، بما ينعكس إيجاباً على أسعار الوحدات
            الإيجارية.
          </li>
          <li class="goal-item">
            وضع السياسات والإجراءات التنظيمية والرقابية لمنشآت الوساطة العقارية
            واعتمادها وتأهيل العاملين فيها.
          </li>
          <li class="goal-item">
            توطين العمل في القطاع، وخلق فرص وظيفية جديدة وملائمة.
          </li>
          <li class="goal-item">
            تقديم خيارات وبدائل إضافية في القطاع العقاري تسهم في دعم برنامج
            الإسكان.
          </li>
          <li class="goal-item">
            تيسير التعامل مع حالات التعثّر عن سداد أجرة المسكن.
          </li>
          <li class="goal-item">
            توفير أدوات وحلول إلكترونية تمثل قيمة مضافة، تسهم في تيسير العملية
            الإيجارية.
          </li>
          <li class="goal-item">
            تحقيق التكامل الرقمي مع القطاعين الحكومي والخاص، مما يعزز الأمن
            الوطني، ويحقق الأهداف الوطنية في التحول الرقمي.
          </li>
        </ol>
      </div>

      <h1 class="aqdi-values">قيم “ عقدي “</h1>

      <!-- AQDI Values -->
      <div class="benefits-container">
        <div class="benefit-item">
          <img
            src="{{ asset('website/asset/images/transparency-value-icon.svg') }}"
            alt="الشفافية"
            class="benefit-image"
          />
          <h3 class="benefit-title">الشفافية</h3>
          <p class="benefit-description">
            توفير معلومات واضحة ومفتوحة بشكل كامل للجمهور بشفافية لتعزيز فهم
            المواطنين.
          </p>
        </div>
        <div class="benefit-item">
          <img
            src="{{ asset('website/asset/images/trust-value-icon.svg') }}"
            alt="الثقة"
            class="benefit-image"
          />
          <h3 class="benefit-title">الثقة</h3>
          <p class="benefit-description">
            ثقتكم تستند إلى موقعنا شفافية ومعلومات دقيقة لخدمتكم بكفاءة.
          </p>
        </div>
        <div class="benefit-item">
          <img
            src="{{ asset('website/asset/images/love-value-icon.svg') }}"
            alt="التوازن"
            class="benefit-image"
          />
          <h3 class="benefit-title">التوازن</h3>
          <p class="benefit-description">
            توفير معلومات واضحة ومفتوحة بشكل كامل للجمهور بشفافية لتعزيز فهم
            المواطنين.
          </p>
        </div>
      </div>

    
    <script>
      document
        .querySelector(".hamburger")
        .addEventListener("click", function () {
          document.querySelector(".sidebar").classList.add("open");
        });

      document
        .querySelector(".close-btn")
        .addEventListener("click", function () {
          document.querySelector(".sidebar").classList.remove("open");
        });
    </script>
@endsection

 