<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    #[OA\Post(
        path: '/api/login',
        summary: 'Login user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Email atau password salah', content: new OA\JsonContent),
            new OA\Response(response: 403, description: 'Akun disuspend / email belum diverifikasi', content: new OA\JsonContent),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent),
        ]
    )]
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());

            if (! $result) {
                return ResponseHelper::error('Email atau password salah', 401);
            }

            return ResponseHelper::success($result, 'Login berhasil');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat login: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server.', 500);
        }
    }

    #[OA\Post(
        path: '/api/register',
        summary: 'Registrasi user baru',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'budi@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Registrasi berhasil', content: new OA\JsonContent),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent),
        ]
    )]
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());

            return ResponseHelper::success(new UserResource($user), 'Registrasi berhasil');
        } catch (\Throwable $e) {
            Log::error('Error saat register: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server.', 500);
        }
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout berhasil', content: new OA\JsonContent),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent),
        ]
    )]
    public function logout()
    {
        if (! $this->authService->logout()) {
            return ResponseHelper::error('Gagal logout.', 500);
        }

        return ResponseHelper::success(null, 'Logout berhasil');
    }

    #[OA\Post(
        path: '/api/refresh',
        summary: 'Refresh token JWT',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token diperbarui', content: new OA\JsonContent),
            new OA\Response(response: 401, description: 'Token tidak valid', content: new OA\JsonContent),
        ]
    )]
    public function refresh()
    {
        try {
            $result = $this->authService->refreshToken();

            return ResponseHelper::success($result, 'Token berhasil diperbarui');
        } catch (JWTException $e) {
            return ResponseHelper::error('Token tidak valid atau sudah tidak bisa di-refresh.', 401);
        }
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Data user yang sedang login',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Berhasil', content: new OA\JsonContent),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent),
        ]
    )]
    public function me()
    {
        return ResponseHelper::success(
            new UserResource($this->authService->me()),
            'Data user berhasil diambil'
        );
    }
}
