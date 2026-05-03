@extends('website.auth.app')

@section('title', 'الأسئلة الشائعة | عقدي')

@section('content')

<!-- Meta Title and Meta Description --> 
@section('meta')
    <meta name="title" content="الأسئلة الأسئلة الشائعة | عقدي - كل ما تحتاج معرفته عن خدماتنا وحلولنا">
    <meta name="description" content="تعرف على إجابات الأسئلة الشائعة حول خدمات عقدي وحلولها الإلكترونية لتطوير قطاع الإيجار العقاري بالتعاون مع ايجار والهيئة العامة للعقار">
@endsection

<section class="faq-section">
  <h2 class="faq-header"> الأسئلة الشائعة | عقدي  </h2>

  <div class="accordion">
      <!-- Loop through each question --> 
      @foreach($Questions as $index => $question)
          <div class="accordion-item"> 
              <!-- Accordion Header -->
              <div class="accordion-header" data-target="content-{{ $index }}">
                  <span>{{ $question->title_trans }}</span>
                  <i class="fa-solid fa-chevron-down arrow-icon"></i>
              </div>
          </div>

          <!-- Accordion Content (Separate Box) -->
          <div class="accordion-content" id="content-{{ $index }}">
              <p>{{ $question->answer_trans }}</p>
          </div>
      @endforeach
  </div>
</section>
   
    <script>
    //   document
    //     .querySelector(".hamburger")
    //     .addEventListener("click", function () {
    //       document.querySelector(".sidebar").classList.add("open");
    //     });

      document
        .querySelector(".close-btn")
        .addEventListener("click", function () {
          document.querySelector(".sidebar").classList.remove("open");
        });

      document.addEventListener("DOMContentLoaded", function () {
        const accordionHeaders = document.querySelectorAll(".accordion-header");

        accordionHeaders.forEach((header) => {
          header.addEventListener("click", function () {
            const targetId = this.getAttribute("data-target");
            const targetContent = document.getElementById(targetId);
            const arrowIcon = this.querySelector(".arrow-icon");
            
              // Toggle display of the content
              if (targetContent.style.display === "block") {
                // Content is visible, hide it
                targetContent.style.display = "none";
                arrowIcon.style.transform = "rotate(0deg)";
                arrowIcon.style.backgroundColor = "#F3F3F3";
                arrowIcon.style.color = "#000";
              } else {
                // Show the clicked content and adjust arrow icon styles
                targetContent.style.display = "block";
                arrowIcon.style.transform = "rotate(180deg)";
                arrowIcon.style.backgroundColor = "#1C7C70";
                arrowIcon.style.color = "#fff";
              }
            
          });
        });
      });
    </script>
@endsection
