<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Mail\OtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\EmailVerificationRepositoryInterface;
use App\Repositories\Contracts\PasswordResetRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * @extends BaseService<User>
 *
 * @phpstan-type TokenPair array{
 *     access_token: string,
 *     token_type: string,
 *     expires_in: int,
 *     refresh_token: string,
 *     refresh_expires_at: CarbonInterface
 * }
 * @phpstan-type ClientMeta array{user_agent?: string|null, ip_address?: string|null}
 */
class AuthService extends BaseService
{
    /**
     * Jika refresh token yang baru saja dirotasi dipakai lagi dalam rentang ini
     * (detik), anggap sebagai request bersamaan (mis. dua tab), bukan pencurian.
     */
    private const REUSE_GRACE_SECONDS = 10;

    public function __construct(
        protected AuthRepositoryInterface $authRepository,
        protected EmailVerificationRepositoryInterface $emailVerificationRepository,
        protected PasswordResetRepositoryInterface $passwordResetRepository,
    ) {
        parent::__construct($authRepository);
    }

    /**
     * Registrasi user baru lalu kirim OTP verifikasi ke email.
     * Gagal kirim email tidak membatalkan registrasi; user bisa minta kirim ulang.
     *
     * @param array<string, mixed> $data
     */
    public function register(array $data): User
    {
        /** @var User $user */
        $user = $this->create($data);

        try {
            $this->sendVerificationOtp($user);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim OTP registrasi: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);
        }

        return $user;
    }

    /**
     * Verifikasi email dengan kode OTP.
     *
     * @throws AuthException 404 user tidak ditemukan, 422 OTP salah/kedaluwarsa, 429 terlalu banyak percobaan
     */
    public function verifyEmailOtp(string $email, string $otp): User
    {
        $user = $this->findUnverifiedUser($email);

        [$status, $remaining] = $this->emailVerificationRepository->verifyOtp($user, $otp);

        return match ($status) {
            'ok' => $user->refresh(),
            'expired' => throw new AuthException('Kode OTP sudah kedaluwarsa. Silakan minta kode baru.', 422),
            'too_many_attempts' => throw new AuthException('Terlalu banyak percobaan. Silakan minta kode baru.', 429),
            'invalid' => throw new AuthException("Kode OTP salah. Sisa percobaan: {$remaining}.", 422),
            default => throw new AuthException('Kode OTP tidak ditemukan. Silakan minta kode baru.', 422),
        };
    }

    /**
     * Kirim ulang OTP verifikasi email.
     *
     * @throws AuthException 404 user tidak ditemukan, 422 sudah terverifikasi, 429 terlalu cepat
     */
    public function resendEmailOtp(string $email): void
    {
        $user = $this->findUnverifiedUser($email);

        $wait = $this->emailVerificationRepository->secondsUntilResend($user);

        if ($wait > 0) {
            throw new AuthException("Tunggu {$wait} detik sebelum meminta kode baru.", 429);
        }

        $this->sendVerificationOtp($user);
    }

    /**
     * Login dengan email + password.
     * Mengembalikan null jika email/password salah.
     *
     * @param array{email: string, password: string} $credentials
     * @param ClientMeta $meta
     * @return TokenPair|null
     *
     * @throws AuthException Akun disuspend / email belum diverifikasi
     */
    public function login(array $credentials, bool $remember = true, array $meta = []): ?array
    {
        $user = $this->authRepository->validateEmailCredentials($credentials);

        if ($user === null) {
            return null;
        }

        return $this->issueTokens($user, $remember, $meta);
    }

    /**
     * Login dengan Google: verifikasi ID token ("credential") dari front end,
     * cari/buat user, lalu kembalikan pasangan token aplikasi.
     *
     * @param ClientMeta $meta
     * @return TokenPair
     *
     * @throws AuthException 401 token tidak valid, 403 email belum diverifikasi / akun disuspend
     */
    public function verifyIdTokenGoogle(string $credential, bool $remember = true, array $meta = []): ?array
    {
        $payload = $this->authRepository->verifyIdToken($credential);

        if ($payload === null) {
            throw new AuthException('Token Google tidak valid atau sudah kedaluwarsa', 401);
        }

        if (empty($payload['email']) || ! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw new AuthException('Email akun Google belum terverifikasi', 403);
        }

        $user = $this->findOrCreateUser($payload);

        return $this->issueTokens($user, $remember, $meta);
    }

    /**
     * Logout dari perangkat ini: cabut refresh token dan batalkan access token.
     */
    public function logout(?string $plainRefreshToken = null): bool
    {
        if ($plainRefreshToken !== null && $plainRefreshToken !== '') {
            $this->authRepository->revokeRefreshTokenByPlain($plainRefreshToken);
        }

        $this->authRepository->invalidateAccessToken();

        return true;
    }

    /**
     * Tukar refresh token dengan pasangan token baru (rotation).
     * Refresh token lama langsung tidak berlaku.
     *
     * @param ClientMeta $meta
     * @return TokenPair
     *
     * @throws AuthException 401 sesi tidak valid/berakhir, 403 akun disuspend
     */
    public function refreshToken(string $plainRefreshToken, array $meta = []): array
    {
        // Status dikembalikan dari transaction (bukan langsung throw di dalamnya)
        // supaya pencabutan token karena reuse/suspend tetap tersimpan.
        [$status, $tokens] = DB::transaction(function () use ($plainRefreshToken, $meta): array {
            $record = $this->authRepository->findRefreshTokenForUpdate($plainRefreshToken);

            if ($record === null) {
                return ['invalid', null];
            }

            if ($record->revoked_at !== null) {
                // Token lama dipakai lagi setelah masa jeda: kemungkinan besar dicuri.
                // Cabut semua sesi user ini supaya pencuri ikut ter-logout.
                if ($record->revoked_at->lt(now()->subSeconds(self::REUSE_GRACE_SECONDS))) {
                    $this->authRepository->revokeAllRefreshTokens((string) $record->user_id);

                    return ['reused', null];
                }

                return ['invalid', null];
            }

            if ($record->expires_at->isPast()) {
                return ['expired', null];
            }

            $user = $record->user;

            if ($user === null) {
                $this->authRepository->revokeAllRefreshTokens((string) $record->user_id);

                return ['inactive', null];
            }

            // Rotation: token lama dicabut, token baru diterbitkan.
            $this->authRepository->revokeRefreshToken($record);

            return ['ok', $this->issueTokens($user, $record->remember, $meta)];
        });

        return match ($status) {
            'ok' => $tokens,
            'inactive' => throw new AuthException('Akun Anda dinonaktifkan', 403),
            default => throw new AuthException('Sesi berakhir, silakan login kembali', 401),
        };
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
     * Cari user berdasarkan google_id atau email; buat baru jika belum ada.
     *
     * @param array<string, mixed> $payload payload ID token Google yang sudah terverifikasi
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

    /**
     * Terbitkan access token (JWT) + refresh token untuk user.
     *
     * @param ClientMeta $meta
     * @return TokenPair
     */
    private function issueTokens(User $user, bool $remember, array $meta): array
    {
        $accessToken = $this->authRepository->loginWithUser($user);

        [$refreshToken, $record] = $this->authRepository->createRefreshToken($user, $remember, $meta);

        return [
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => $this->authRepository->accessTokenTtl() * 60,
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $record->expires_at,
        ];
    }

    /**
     * @return array{user_agent: string|null, ip_address: string|null}
     */
    public function clientMeta(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];
    }

    /**
     * Buat OTP baru lewat repository lalu kirim ke email user.
     */
    private function sendVerificationOtp(User $user): void
    {
        $code = $this->emailVerificationRepository->createOtp($user);

        Mail::to($user->email)->send(
            new OtpMail($user->name, $code, (int) config('otp.expires_in', 10))
        );
    }

    /**
     * @throws AuthException
     */
    private function findUnverifiedUser(string $email): User
    {
        $user = $this->authRepository->findByEmail(Str::lower($email));

        if ($user === null) {
            throw new AuthException('User tidak ditemukan', 404);
        }

        if ($user->email_verified_at !== null) {
            throw new AuthException('Email sudah terverifikasi', 422);
        }

        return $user;
    }

    /**
     * Mengirim OTP untuk proses reset password.
     *
     * @throws AuthException
     */
    public function forgotPassword(string $email): void
    {
        $user = $this->authRepository->findByEmail(Str::lower($email));

        if ($user === null) {
            throw new AuthException('Email tidak terdaftar', 404);
        }

        $code = $this->passwordResetRepository->createOtp($user);

        Mail::to($user->email)->send(
            new PasswordResetOtpMail(
                $user->name,
                $code,
                (int) config('otp.expires_in', 10)
            )
        );
    }

    /**
     * Reset password menggunakan OTP.
     *
     * @throws AuthException
     */
    public function resetPassword(
        string $email,
        string $otp,
        string $password
    ): void {
        $user = $this->authRepository->findByEmail(Str::lower($email));

        if ($user === null) {
            throw new AuthException('Email tidak terdaftar', 404);
        }

        [$status, $remaining] = DB::transaction(function () use ($user, $otp, $password): array {
            [$status, $remaining] = $this->passwordResetRepository->verifyOtp($user, $otp);

            if ($status !== 'ok') {
                return [$status, $remaining];
            }

            $user->forceFill([
                'password' => $password,
            ])->save();

            $this->passwordResetRepository->deleteOtp($user);

            $this->authRepository->revokeAllRefreshTokens((string) $user->id);

            return ['ok', 0];
        });

        match ($status) {
            'ok' => null,
            'expired' => throw new AuthException('Kode OTP sudah kedaluwarsa. Silakan minta kode baru.', 422),
            'too_many_attempts' => throw new AuthException('Terlalu banyak percobaan. Silakan minta kode baru.', 429),
            'invalid' => throw new AuthException("Kode OTP salah. Sisa percobaan: {$remaining}.", 422),
            default => throw new AuthException('Kode OTP tidak ditemukan. Silakan minta kode baru.', 422),
        };
    }
}
