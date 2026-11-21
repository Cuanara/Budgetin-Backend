<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

/**
 * @extends BaseService<User>
 */
class AuthService extends BaseService
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository
    ) {
        parent::__construct($authRepository);
    }

    /**
     * Login user dan kembalikan token JWT.
     * Mengembalikan null jika email/password salah.
     *
     * @param  array<string, mixed>  $credentials
     *
     * @throws AuthException Akun disuspend / email belum diverifikasi
     */
    public function login(array $credentials): ?string
    {
        return $this->authRepository->loginEmail($credentials);
    }

    /**
     * Logout user (invalidate token).
     */
    public function logout(): bool
    {
        return $this->authRepository->logout();
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

    /**
     * Login dengan Google: verifikasi ID token ("credential") dari front end,
     * cari/buat user, lalu kembalikan token JWT aplikasi.
     *
     * @param  array<string, mixed>  $data  berisi key "credential"
     *
     * @throws AuthException 401 token tidak valid, 403 email belum diverifikasi / akun disuspend
     */
    public function verifyIdTokenGoogle(array $data): string
    {
        $payload = $this->authRepository->verifyIdToken((string) $data['credential']);

        if ($payload === null) {
            throw new AuthException('Token Google tidak valid atau sudah kedaluwarsa', 401);
        }

        if (empty($payload['email']) || ! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw new AuthException('Email akun Google belum terverifikasi', 403);
        }

        $user = $this->findOrCreateUser($payload);

        return $this->authRepository->loginWithUser($user);
    }

    /**
     * Cari user berdasarkan google_id atau email; buat baru jika belum ada.
     *
     * @param  array<string, mixed>  $payload  payload ID token Google yang sudah terverifikasi
     */
    private function findOrCreateUser(array $payload): User
    {
        return DB::transaction(function () use ($payload): User {
            $googleId = (string) $payload['sub'];
            $email = Str::lower((string) $payload['email']);

            $user = $this->authRepository->findByGoogleId($googleId)
                ?? $this->authRepository->findByEmail($email);

            if ($user) {
                // Hubungkan akun lama (daftar via email) dengan akun Google.
                return $this->authRepository->updateGoogleData($user, [
                    'google_id' => $googleId,
                    'avatar' => $payload['picture'] ?? $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            return $this->authRepository->createGoogleUser([
                'name' => $payload['name'] ?? Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $payload['picture'] ?? null,
                'password' => null, // login hanya via Google
                'email_verified_at' => now(),
            ]);
        });
    }
}
