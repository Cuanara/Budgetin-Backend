<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthService extends BaseService
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository
    ) {
        parent::__construct($authRepository);
    }

    /**
     * Login user dan kembalikan data token.
     * Mengembalikan null jika email/password salah.
     *
     * @throws AuthException Akun disuspend / email belum diverifikasi
     */
    public function login(array $credentials): ?string
    {
        return $this->authRepository->login($credentials);
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
     * @throws JWTException
     */
    public function refreshToken(): string
    {
        return $this->authRepository->refreshToken();
    }

    /**
     * Ambil data user yang sedang login.
     */
    public function me(): ?User
    {
        $id = Auth::guard('api')->id();

        return $id === null ? null : $this->authRepository->find((string) $id);
    }
}
