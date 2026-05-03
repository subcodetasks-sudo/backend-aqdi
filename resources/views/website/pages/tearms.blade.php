@extends('website.auth.app')

@section('title', 'الشروط والأحكام | عقدي')

@section('meta')
    <!-- Meta Title and Meta Description -->
    <meta charset="UTF-8">
    <meta name="title" content="الشروط والأحكام | عقدي">
    <meta name="description" content="تعرف على الشروط والأحكام لعمل عقد إلكتروني عبر موقع عقدي، بما في ذلك التزامات المؤجر والوسيط، وآلية تسوية الخلافات، وإجراءات التحديث والتواصل.">
@endsection

@section('content')

<section class="faq-section">
    <h1 class="faq-header">الشروط والأحكام | عقدي</h1>

    <p>
        {!! $descriptions !!}
    </p>
</section>

@endsection
