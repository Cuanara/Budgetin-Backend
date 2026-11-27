<?php

namespace App\Repositories;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Repositories\Contracts\PasswordResetRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetRepository extends BaseRepository implements PasswordResetRepositoryInterface
{
    protected function model(): string
    {
        return PasswordResetOtp::class;
    }

    public function createOtp(User $user): string
    {
        $length = (int) config('otp.length', 6);
        $expiresIn = (int) config('otp.expires_in', 10);

        $code = str_pad(
            (string) random_int(0, (10 ** $length) - 1),
            $length,
            '0',
            STR_PAD_LEFT
        );

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
        return DB::transaction(function () use ($user, $code): array {
            /** @var PasswordResetOtp|null $otp */
            $otp = $this->queryForUser($user)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return ['not_found', 0];
            }

            if ($otp->isExpired()) {
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

            return ['ok', 0];
        });
    }

    public function secondsUntilResend(User $user): int
    {
        /** @var PasswordResetOtp|null $otp */
        $otp = $this->queryForUser($user)
            ->latest('created_at')
            ->first();

        if ($otp === null) {
            return 0;
        }

        $cooldown = (int) config('otp.resend_cooldown', 60);
        $elapsed = now()->getTimestamp() - $otp->created_at->getTimestamp();

        return max($cooldown - $elapsed, 0);
    }

    public function deleteOtp(User $user): void
    {
        $this->queryForUser($user)->delete();
    }

    /**
     * @return Builder<PasswordResetOtp>
     */
    private function queryForUser(User $user): Builder
    {
        return $this->model
            ->newQuery()
            ->where('user_id', $user->id);
    }
}