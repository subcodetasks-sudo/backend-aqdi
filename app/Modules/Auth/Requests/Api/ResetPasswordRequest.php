<?php

namespace App\Modules\Auth\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => 'required|exists:users,mobile',
            'code' => 'required',
            'password' => 'required|confirmed',
        ];
    }
}
