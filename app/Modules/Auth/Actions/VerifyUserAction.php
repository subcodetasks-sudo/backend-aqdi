<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Http\Request;

class VerifyUserAction
{
    /**
     * @return array{ok: true}|array{ok: false, message: string}
     */
    public function execute(Request $request): array
    {
        $user = User::where('mobile', $request->mobile)->firstOrFail();

        if ($user->verification_code == $request->verification_code) {
            $user->email_verified_at = now();
            $user->save();

            return ['ok' => true];
        }

        return ['ok' => false, 'message' => trans('api.verification_faild')];
    }
}
