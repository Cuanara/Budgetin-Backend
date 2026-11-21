<?php

namespace App\Repositories;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

/**
 * @extends BaseRepository<User>
 */
class AuthRepository extends BaseRepository implements AuthRepositoryInterface
{
    /** Masa berlaku token dalam menit. */
    private const TOKEN_TTL = 60;

    protected function model(): string
    {
        return User::class;
    }

    /**
     * Guard 'api' memakai driver jwt, jadi aslinya berupa JWTGuard.
     * Method ini membuat tipe tersebut eksplisit agar IDE mengenali
     * method seperti setTTL() dan getLastAttempted().
     */
    protected function jwtGuard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        return $guard;
    }

    public function loginWithUser(User $user): string
    {
        return $this->jwtGuard()
            ->setTTL(self::TOKEN_TTL)
            ->login($user);
    }

    /**
     * @param  array<string, mixed>  $credentials
     *
     * @throws AuthException jika akun disuspend / email belum diverifikasi
     */
    public function loginEmail(array $credentials): ?string
    {
        $guard = $this->jwtGuard();

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
            throw new AuthException('Email belum diverifikasi. Silakan cek email Anda', 403);
        }

        return $this->loginWithUser($user);
    }

    public function logout(): bool
    {
        try {
            $this->jwtGuard()->logout();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function refreshToken(): string
    {
        JWTAuth::factory()->setTTL(self::TOKEN_TTL);

        return JWTAuth::parseToken()->refresh();
    }

    /**
     * @return int jumlah baris yang diperbarui
     */
    public function updateActiveStatus(string $userId, bool $isActive): int
    {
        return User::where('id', $userId)->update(['is_active' => $isActive]);
    }

    public function saveResetToken(string $email, string $hashedOtp): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $hashedOtp, 'created_at' => now()]
        );
    }

    public function deleteResetToken(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
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
}
