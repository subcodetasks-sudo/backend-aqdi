<x-mail::message>
# Verification

Your verification code is **{{ $user->verification_code }}**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
