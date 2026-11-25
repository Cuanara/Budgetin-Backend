<?php

namespace App\Repositories;

use App\Exceptions\AuthException;
use App\Helpers\AuthContextHelper;
use App\Models\RefreshToken;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @extends BaseRepository<User>
 */
class AuthRepository extends BaseRepository implements AuthRepositoryInterface
{
    /** Masa berlaku access token (menit) di APP_ENV=local, dibuat lebih lama supaya enak saat development. */
    private const ACCESS_TOKEN_TTL_LOCAL = 60;

    /** Masa berlaku access token (menit) di production dan environment lain. */
    private const ACCESS_TOKEN_TTL_PRODUCTION = 15;

    /** Masa berlaku refresh token dalam hari jika user memilih "ingat saya". */
    private const REFRESH_TOKEN_TTL_REMEMBER = 30;

    /** Masa berlaku refresh token dalam hari jika user tidak memilih "ingat saya". */
    private const REFRESH_TOKEN_TTL_SHORT = 1;

    protected function model(): string
    {
        return User::class;
    }

    /**
     * Masa berlaku access token dalam menit.
     */
    public function accessTokenTtl(): int
    {
        return app()->isLocal()
            ? self::ACCESS_TOKEN_TTL_LOCAL
            : self::ACCESS_TOKEN_TTL_PRODUCTION;
    }

    public function loginWithUser(User $user): string
    {
        return AuthContextHelper::userLogin()
            ->setTTL($this->accessTokenTtl())
            ->login($user);
    }

    /**
     * @param  array<string, mixed>  $credentials
     *
     * @throws AuthException jika akun disuspend / email belum diverifikasi
     */
    public function validateEmailCredentials(array $credentials): ?User
    {
        $guard = AuthContextHelper::userLogin();

        $credentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (! $guard->validate($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = $guard->getLastAttempted();

        if (is_null($user->email_verified_at)) {
            throw new AuthException('Email belum diverifikasi', 403);
        }

        return $user;
    }

    public function updateUserPassword(string $email, string $hashedPassword): void
    {
        User::where('email', $email)->update(['password' => $hashedPassword]);
    }

    public function markTokenAsVerified(string $email, string $verifiedToken): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->update([
                'token' => $verifiedToken,
                'created_at' => now(),
            ]);
    }

    public function saveEmailVerificationOtp(string $email, string $hashedOtp): void
    {
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'otp' => $hashedOtp,
                'expires_at' => now()->addMinutes(2),
                'created_at' => now(),
            ]
        );
    }

    /**
     * @return object|null baris email_verification_tokens (email, otp, expires_at, created_at)
     */
    public function findEmailVerificationOtp(string $email): ?object
    {
        return DB::table('email_verification_tokens')->where('email', $email)->first();
    }

    public function deleteEmailVerificationOtp(string $email): void
    {
        DB::table('email_verification_tokens')->where('email', $email)->delete();
    }

    public function markEmailAsVerified(string $userId): void
    {
        User::where('id', $userId)->update(['email_verified_at' => now()]);
    }

    /**
     * @return array<string, mixed>|null payload token jika valid, null jika tidak
     */
    public function verifyIdToken(string $idToken): ?array
    {
        $allowedClientIds = config('services.google.client_ids', []);

        if (empty($allowedClientIds)) {
            Log::error('GOOGLE_CLIENT_ID belum diisi di .env');

            return null;
        }

        try {
            $client = new GoogleClient;
            // Audience dicek manual di bawah supaya bisa menerima beberapa
            // Client ID sekaligus (mis. Web + Android + iOS).
            $payload = $client->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            Log::warning('Verifikasi token Google gagal: '.$e->getMessage());

            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        // Token harus dibuat untuk aplikasi kita, bukan aplikasi lain.
        if (! in_array($payload['aud'] ?? null, $allowedClientIds, true)) {
            Log::warning('Audience token Google tidak cocok', ['aud' => $payload['aud'] ?? null]);

            return null;
        }

        // Opsional: batasi login hanya untuk domain Google Workspace tertentu.
        $hostedDomain = config('services.google.hosted_domain');
        if ($hostedDomain && ($payload['hd'] ?? null) !== $hostedDomain) {
            return null;
        }

        return $payload;
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->findBy('google_id', $googleId);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', Str::lower($email));
    }

    /**
     * Buat user baru dari data Google.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createGoogleUser(array $attributes): User
    {
        $user = new User;
        $user->forceFill($attributes)->save();

        return $user;
    }

    /**
     * Hubungkan atau perbarui data Google pada user yang sudah ada.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateGoogleData(User $user, array $attributes): User
    {
        $user->forceFill($attributes)->save();

        return $user;
    }

    /**
     * Buat refresh token baru untuk user.
     *
     * @param  array{user_agent?: string|null, ip_address?: string|null}  $meta
     * @return array{0: string, 1: RefreshToken} [token asli untuk client, record di database]
     */
    public function createRefreshToken(User $user, bool $remember, array $meta = []): array
    {
        $plainToken = Str::random(80);
        $days = $remember ? self::REFRESH_TOKEN_TTL_REMEMBER : self::REFRESH_TOKEN_TTL_SHORT;

        $record = RefreshToken::create([
            'user_id' => $user->getKey(),
            'token_hash' => $this->hashToken($plainToken),
            'remember' => $remember,
            'user_agent' => isset($meta['user_agent']) ? Str::limit((string) $meta['user_agent'], 255, '') : null,
            'ip_address' => $meta['ip_address'] ?? null,
            'expires_at' => now()->addDays($days),
        ]);

        return [$plainToken, $record];
    }

    /**
     * Cari refresh token dan kunci barisnya (wajib dipanggil di dalam DB::transaction)
     * supaya dua request refresh bersamaan tidak sama-sama lolos.
     */
    public function findRefreshTokenForUpdate(string $plainToken): ?RefreshToken
    {
        return RefreshToken::where('token_hash', $this->hashToken($plainToken))
            ->lockForUpdate()
            ->first();
    }

    /**
     * Cabut semua sesi milik user (logout dari semua perangkat).
     *
     * @return int jumlah token yang dicabut
     */
    public function revokeAllRefreshTokens(string $userId): int
    {
        return RefreshToken::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeRefreshTokenByPlain(string $plainToken): void
    {
        RefreshToken::where('token_hash', $this->hashToken($plainToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeRefreshToken(RefreshToken $token): void
    {
        $token->forceFill(['revoked_at' => now()])->save();
    }

    public function invalidateAccessToken(): void
    {
        try {
            AuthContextHelper::userLogin()->logout();
        } catch (\Throwable) {
            // token tidak ada / sudah expired: tidak perlu dibatalkan
        }
    }

    private function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
