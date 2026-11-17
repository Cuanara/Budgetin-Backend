<?php

namespace App\Repositories;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

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

    /**
     * @throws AuthException jika akun disuspend / email belum diverifikasi
     */
    public function login(array $credentials): ?array
    {
        $guard = $this->jwtGuard();

        $credentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        // Cek email & password dulu TANPA membuat token
        if (! $guard->validate($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = $guard->getLastAttempted();

        if ($user->is_suspended) {
            throw new AuthException('Akun Anda telah disuspend.', 403);
        }

        if (is_null($user->email_verified_at)) {
            throw new AuthException('Email belum diverifikasi. Silakan cek email Anda.', 403);
        }

        // Baru buat token setelah semua pengecekan lolos
        $token = $guard->setTTL(self::TOKEN_TTL)->login($user);

        return $this->formatTokenResponse($token);
    }

    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $user;
    }

    public function logout($request = null): bool
    {
        try {
            Auth::guard('api')->logout();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function refreshToken(): array
    {
        JWTAuth::factory()->setTTL(self::TOKEN_TTL);
        $newToken = JWTAuth::parseToken()->refresh();

        return $this->formatTokenResponse($newToken);
    }

    public function getAuthenticatedUser(): array
    {
        $user = User::find(Auth::guard('api')->id());

        return [
            'user' => $user,
        ];
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updateActiveStatus(string $userId, bool $isActive)
    {
        return User::where('id', $userId)->update(['is_active' => $isActive]);
    }

    protected function formatTokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            // expires_in dalam detik (TTL JWT dalam menit)
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }

    public function findResetToken(string $email)
    {
        return DB::table('password_reset_tokens')->where('email', $email)->first();
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

    public function findByVerifiedToken(string $verifiedToken)
    {
        return DB::table('password_reset_tokens')
            ->where('token', $verifiedToken)
            ->first();
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

    public function findEmailVerificationOtp(string $email)
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

    public function createOrUpdateGoogleUser(array $googleData)
    {
        $user = User::withTrashed()->where('email', $googleData['email'])->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleData['name'],
                'email' => $googleData['email'],
                'password' => Hash::make(Str::random(24)),
                'avatar' => $googleData['picture'] ?? null,
                'email_verified_at' => now(),
                'is_active' => true,
                'is_suspended' => false,
            ]);

            $user->assignRole('user');
        } else {
            if ($user->trashed()) {
                $user->restore();
            }

            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            if (empty($user->name) && isset($googleData['name'])) {
                $user->name = $googleData['name'];
            }

            if (empty($user->avatar) && isset($googleData['picture'])) {
                $user->avatar = $googleData['picture'];
            }

            $user->is_active = true;
            $user->is_suspended = false;
            $user->save();
        }

        return $user->fresh(['departements', 'teams', 'roles']);
    }

    public function generateTokenForUser(User $user): ?array
    {
        JWTAuth::factory()->setTTL(self::TOKEN_TTL);
        $token = JWTAuth::fromUser($user);

        if (! $token) {
            return null;
        }

        return $this->formatTokenResponse($token);
    }
}