<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Helpers\RefreshTokenCookie;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginGoogleRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

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
                    new OA\Property(property: 'remember', type: 'boolean', example: true),
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
            $tokens = $this->authService->login(
                $request->credentials(),
                $request->remember(),
                $this->authService->clientMeta($request)
            );

            if ($tokens === null) {
                return ResponseHelper::error('Email atau password salah', 401);
            }

            return RefreshTokenCookie::attach(
                $request,
                ResponseHelper::success(new TokenResource($tokens), 'Login berhasil'),
                $tokens
            );
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
                required: ['credential', 'remember'],
                properties: [
                    new OA\Property(property: 'credential', type: 'string', example: 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjFhMmIzYzRkNWU2ZiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJhdWQiOiIxMjM0NTY3ODkwLWFiYy5hcHBzLmdvb2dsZXVzZXJjb250ZW50LmNvbSIsInN1YiI6IjEwODc2NTQzMjEwOTg3NjU0MzIxMCIsImVtYWlsIjoiYnVkaUBnbWFpbC5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZX0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'),
                    new OA\Property(property: 'remember', type: 'boolean', example: true),
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
            $token = $this->authService->verifyIdTokenGoogle(
                $request->credential(),
                $request->remember(),
                $this->authService->clientMeta($request)
            );

            if (! $token) {
                return ResponseHelper::error('Token Google tidak valid atau sudah kedaluwarsa', 401);
            }

            return RefreshTokenCookie::attach(
                $request,
                ResponseHelper::success(new TokenResource($token), 'Login Google berhasil'),
                $token
            );
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
            $user = $this->authService->register($request->validated());

            return ResponseHelper::success(new UserResource($user), 'Registrasi berhasil. Kode OTP telah dikirim ke email Anda.');
        } catch (\Throwable $e) {
            Log::error('Error saat register: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }

    #[OA\Post(
        path: '/api/verify-otp',
        summary: 'Verifikasi email dengan kode OTP',
        description: 'Mencocokkan kode OTP yang dikirim ke email saat registrasi. Jika benar, email_verified_at diisi dan user dapat login.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'otp', type: 'string', minLength: 6, maxLength: 6, pattern: '^[0-9]{6}$', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email berhasil diverifikasi'),
            new OA\Response(response: 404, description: 'User tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal / kode OTP salah / kedaluwarsa / tidak ditemukan / email sudah terverifikasi'),
            new OA\Response(response: 429, description: 'Terlalu banyak percobaan, silakan minta kode baru'),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server'),
        ]
    )]
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->verifyEmailOtp(
                $request->validated('email'),
                $request->validated('otp'),
            );

            return ResponseHelper::success(new UserResource($user), 'Email berhasil diverifikasi');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat verifikasi OTP: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }

    #[OA\Post(
        path: '/api/resend-otp',
        summary: 'Kirim ulang kode OTP verifikasi email',
        description: 'Mengirim kode OTP baru ke email. Kode lama otomatis tidak berlaku. Ada jeda minimal 60 detik antar permintaan.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Kode OTP baru telah dikirim ke email'),
            new OA\Response(response: 404, description: 'User tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal / email sudah terverifikasi'),
            new OA\Response(response: 429, description: 'Terlalu cepat meminta kode baru'),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server'),
        ]
    )]
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $this->authService->resendEmailOtp($request->validated('email'));

            return ResponseHelper::success(null, 'Kode OTP baru telah dikirim ke email Anda');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat kirim ulang OTP: '.$e->getMessage(), ['exception' => $e]);

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
    public function logout(Request $request): JsonResponse
    {
        if (! $this->authService->logout(RefreshTokenCookie::read($request))) {
            return ResponseHelper::error('Gagal logout', 500);
        }

        return ResponseHelper::success(null, 'Logout berhasil');
    }

    #[OA\Post(
        path: '/api/refresh',
        summary: 'Refresh token JWT',
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(name: 'X-Client-Platform', in: 'header', required: false, schema: new OA\Schema(type: 'string', enum: ['mobile'])),
        ],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Sesi berakhir, silakan login kembali'),
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $plainToken = RefreshTokenCookie::read($request);

        if ($plainToken === null) {
            return ResponseHelper::error('Sesi berakhir, silakan login kembali', 401)
                ->withCookie(RefreshTokenCookie::forget());
        }

        try {
            $tokens = $this->authService->refreshToken($plainToken, $this->authService->clientMeta($request));

            return ResponseHelper::success(new TokenResource($tokens), 'Token berhasil diperbarui');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getCode() ?: 401)
                ->withCookie(RefreshTokenCookie::forget());
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
