<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

final class RefreshTokenCookie
{
    public const COOKIE_NAME = 'refresh_token';

    /** Samakan dengan prefix route auth. */
    public const COOKIE_PATH = '/api';

    /** 'strict' jika frontend & API satu site; 'none' (wajib HTTPS) jika beda domain. */
    public const COOKIE_SAME_SITE = Cookie::SAMESITE_STRICT;

    /** Aplikasi mobile mengirim header ini agar refresh token dikirim di body. */
    public const MOBILE_HEADER = 'X-Client-Platform';

    public static function isMobile(Request $request): bool
    {
        return $request->header(self::MOBILE_HEADER) === 'mobile';
    }

    /**
     * Tempelkan cookie refresh token ke response (khusus client web).
     *
     * @param  array{refresh_token: string, refresh_expires_at: \DateTimeInterface}  $tokens
     */
    public static function attach(Request $request, JsonResponse $response, array $tokens): JsonResponse
    {
        if (self::isMobile($request)) {
            return $response;
        }

        return $response->withCookie(Cookie::create(
            self::COOKIE_NAME,
            $tokens['refresh_token'],
            $tokens['refresh_expires_at'],
            self::COOKIE_PATH,
            config('session.domain'),
            app()->isProduction(), // secure
            true,                  // httpOnly
            false,
            self::COOKIE_SAME_SITE
        ));
    }

    public static function forget(): Cookie
    {
        return cookie()->forget(self::COOKIE_NAME, self::COOKIE_PATH, config('session.domain'));
    }

    /**
     * Ambil refresh token dari cookie (web) atau body (mobile).
     */
    public static function read(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE_NAME) ?? $request->input('refresh_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function make(string $token, \DateTimeInterface $expiresAt): Cookie
    {
        return Cookie::create(
            self::COOKIE_NAME,
            $token,
            $expiresAt,
            self::COOKIE_PATH,
            config('session.domain'),
            app()->isProduction(), // secure: hanya HTTPS di production
            true,                  // httpOnly
            false,                 // raw
            self::COOKIE_SAME_SITE
        );
    }
}
