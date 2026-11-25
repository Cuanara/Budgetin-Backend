<?php

namespace App\Repositories;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use App\Repositories\Contracts\EmailVerificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @extends BaseRepository<EmailVerificationOtp>
 */
class EmailVerificationRepository extends BaseRepository implements EmailVerificationRepositoryInterface
{
    protected function model(): string
    {
        return EmailVerificationOtp::class;
    }

    public function createOtp(User $user): string
    {
        $length = (int) config('otp.length', 6);
        $expiresIn = (int) config('otp.expires_in', 10);

        // random_int = cryptographically secure
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $code, $expiresIn): void {
            $this->queryForUser($user)->delete();

            $this->create([
                'user_id' => $user->id,
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes($expiresIn),
            ]);
        });

        return $code;
    }

    public function verifyOtp(User $user, string $code): array
    {
        // Status dikembalikan dari transaction (bukan throw di dalamnya)
        // supaya increment attempts / penghapusan OTP tetap tersimpan.
        return DB::transaction(function () use ($user, $code): array {
            /** @var EmailVerificationOtp|null $otp */
            $otp = $this->queryForUser($user)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return ['not_found', 0];
            }

            if ($otp->expires_at->isPast()) {
                $otp->delete();

                return ['expired', 0];
            }

            $maxAttempts = (int) config('otp.max_attempts', 5);

            if ($otp->attempts >= $maxAttempts) {
                $otp->delete();

                return ['too_many_attempts', 0];
            }

            if (! Hash::check($code, $otp->code)) {
                $otp->increment('attempts');
                $remaining = max($maxAttempts - $otp->attempts, 0);

                if ($remaining === 0) {
                    $otp->delete();

                    return ['too_many_attempts', 0];
                }

                return ['invalid', $remaining];
            }

            // forceFill agar tidak perlu menambahkan email_verified_at ke $fillable
            $user->forceFill(['email_verified_at' => now()])->save();
            $otp->delete();

            return ['ok', 0];
        });
    }

    public function secondsUntilResend(User $user): int
    {
        /** @var EmailVerificationOtp|null $otp */
        $otp = $this->queryForUser($user)
            ->latest('id')
            ->first();

        if ($otp === null) {
            return 0;
        }

        $cooldown = (int) config('otp.resend_cooldown', 60);
        $elapsed = now()->getTimestamp() - $otp->created_at->getTimestamp();

        return max($cooldown - $elapsed, 0);
    }

    /**
     * @return Builder<EmailVerificationOtp>
     */
    private function queryForUser(User $user): Builder
    {
        return $this->model
            ->newQuery()
            ->where('user_id', $user->id);
    }
}
