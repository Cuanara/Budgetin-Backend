<?php

namespace App\Services;

use App\Repositories\Contracts\AuthRepositoryInterface;

class AuthService
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository
    ) {}

    /**
     * Login user dan kembalikan data token.
     * Mengembalikan null jika email/password salah.
     *
     * @throws \App\Exceptions\AuthException  Akun disuspend / email belum diverifikasi
     */
    public function login(array $credentials): ?array
    {
        $result = $this->authRepository->login($credentials);

        return $result ?: null;
    }

    /**
     * Registrasi user baru.
     */
    public function register(array $data)
    {
        return $this->authRepository->register($data);
    }

    /**
     * Logout user (invalidate token).
     */
    public function logout(): bool
    {
        return (bool) $this->authRepository->logout();
    }

    /**
     * Refresh token JWT.
     *
     * @throws \PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException
     */
    public function refreshToken(): array
    {
        return $this->authRepository->refreshToken();
    }

    /**
     * Ambil data user yang sedang login.
     */
    public function me()
    {
        return $this->authRepository->getAuthenticatedUser();
    }
}