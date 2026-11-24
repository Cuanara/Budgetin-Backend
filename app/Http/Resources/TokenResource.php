<?php

namespace App\Http\Resources;

use App\Helpers\RefreshTokenCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property array{
 *     access_token: string,
 *     token_type: string,
 *     expires_in: int,
 *     refresh_token: string,
 *     refresh_expires_at: Carbon
 * } $resource
 */
class TokenResource extends JsonResource
{
    /**
     * Tanpa wrapper "data", supaya bentuk response sama dengan sebelumnya.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'access_token' => $this->resource['access_token'],
            'token_type' => $this->resource['token_type'],
            // expires_in dalam detik
            'expires_in' => $this->resource['expires_in'],
        ];

        // Mobile: refresh token dikirim di body untuk disimpan di secure storage.
        if (RefreshTokenCookie::isMobile($request)) {
            $data['refresh_token'] = $this->resource['refresh_token'];
            $data['refresh_expires_at'] = $this->resource['refresh_expires_at']->toIso8601String();
        }

        return $data;
    }

    /**
     * Web: refresh token TIDAK dikirim di body, hanya lewat cookie httpOnly.
     */
    public function withResponse(Request $request, JsonResponse $response): void
    {
        if (RefreshTokenCookie::isMobile($request)) {
            return;
        }

        $response->withCookie(
            RefreshTokenCookie::make(
                $this->resource['refresh_token'],
                $this->resource['refresh_expires_at'],
            )
        );
    }
}
