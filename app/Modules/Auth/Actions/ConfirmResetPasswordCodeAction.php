<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Http\Request;

class ConfirmResetPasswordCodeAction
{
    /**
     * @return array{ok: true}|array{ok: false, message: string}
     */
    public function execute(Request $request): array
    {
        $user = User::where('mobile', $request->mobile)->firstOrFail();

        if ($user->reset_password_code != $request->code) {
            return ['ok' => false, 'message' => trans('api.wrong_otp')];
        }

        return ['ok' => true];
    }
}
