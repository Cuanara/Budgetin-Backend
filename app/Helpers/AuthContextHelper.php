<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use RuntimeException;

class AuthContextHelper
{
    public static function userLogin(): JWTGuard
    {
        $guard = Auth::guard('api');

        if (! $guard instanceof JWTGuard) {
            throw new RuntimeException('Guard "api" harus memakai driver "jwt".');
        }

        return $guard;
    }
}
