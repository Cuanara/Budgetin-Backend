<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginGoogleRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
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
                description: 'Login berhasil'
            ),
            new OA\Response(response: 401, description: 'Email atau password salah'),
            new OA\Response(response: 403, description: 'Email belum diverifikasi'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $token = $this->authService->login($request->validated());

            if (! $token) {
                return ResponseHelper::error('Email atau password salah', 401);
            }

            return ResponseHelper::success(new TokenResource($token), 'Login berhasil');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat login: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }

    #[OA\Post(
        path: '/api/login-google',
        summary: 'Login Google user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['credential', 'device_name'],
                properties: [
                    new OA\Property(property: 'credential', type: 'string', example: 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjFhMmIzYzRkNWU2ZiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJhdWQiOiIxMjM0NTY3ODkwLWFiYy5hcHBzLmdvb2dsZXVzZXJjb250ZW50LmNvbSIsInN1YiI6IjEwODc2NTQzMjEwOTg3NjU0MzIxMCIsImVtYWlsIjoiYnVkaUBnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZX0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'Chrome - Windows'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil'
            ),
            new OA\Response(response: 401, description: 'Token Google tidak valid atau sudah kedaluwarsa'),
            new OA\Response(response: 403, description: 'Email akun Google belum terverifikasi'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function loginGoogle(LoginGoogleRequest $request): JsonResponse
    {
        try {
            $token = $this->authService->verifyIdTokenGoogle($request->validated());

            if (! $token) {
                return ResponseHelper::error('Token Google tidak valid atau sudah kedaluwarsa', 401);
            }

            return ResponseHelper::success(new TokenResource($token), 'Login Google berhasil');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat login: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
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
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Registrasi berhasil'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->create($request->validated());

            return ResponseHelper::success(new UserResource($user), 'Registrasi berhasil');
        } catch (\Throwable $e) {
            Log::error('Error saat register: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout berhasil'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
        ]
    )]
    public function logout(): JsonResponse
    {
        if (! $this->authService->logout()) {
            return ResponseHelper::error('Gagal logout', 500);
        }

        return ResponseHelper::success(null, 'Logout berhasil');
    }

    #[OA\Post(
        path: '/api/refresh',
        summary: 'Refresh token JWT',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid atau sudah tidak bisa di-refresh'),
        ]
    )]
    public function refresh(): JsonResponse
    {
        try {
            $token = $this->authService->refreshToken();

            return ResponseHelper::success(new TokenResource($token), 'Token berhasil diperbarui');
        } catch (JWTException $e) {
            return ResponseHelper::error('Token tidak valid atau sudah tidak bisa di-refresh', 401);
        }
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Data user yang sedang login',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Data user berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
        ]
    )]
    public function me(): JsonResponse
    {
        return ResponseHelper::success(
            new UserResource($this->authService->me()),
            'Data user berhasil diambil'
        );
    }
}
