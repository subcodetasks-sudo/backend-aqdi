<x-mail::message>
# Reset Password

Your reset password code is **{{ $user->reset_password_code }}**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
