<?php

namespace App\Support;

use App\Models\Employee;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedEmployee
{
    public static function from(Request $request): ?Employee
    {
        $user = $request->user();
        if ($user instanceof Employee) {
            return $user;
        }

        $bearer = $request->bearerToken();
        if (! is_string($bearer) || $bearer === '') {
            return null;
        }

        $token = PersonalAccessToken::findToken($bearer);
        if ($token === null) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return null;
        }

        $tokenable = $token->tokenable;
        if (! $tokenable instanceof Employee) {
            return null;
        }

        $tokenable->withAccessToken($token);

        return $tokenable;
    }
}
