@component('mail::message')
<p>مرحبًا {{ $user->name }}</p>

<p>نحن نفهم أنك ترغب في إعادة تعيين كلمة المرور لحسابك.</p>

@component('mail::button', ['url' => url('reset-password/'.$user->remember_token)])
إعادة تعيين كلمة المرور
@endcomponent

<p>في حال كانت لديك أي أسئلة أخرى، لا تتردد في الاتصال بنا.</p>

شكرًا<br/>

{{ config('app.name') }}
@endcomponent
