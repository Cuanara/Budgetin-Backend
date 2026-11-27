<?php

namespace App\Repositories\Contracts;

use App\Models\PasswordResetOtp;
use App\Models\User;

/**
 * @extends BaseRepositoryInterface<PasswordResetOtp>
 */
interface PasswordResetRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Membuat OTP reset password baru.
     * OTP lama milik user akan dihapus.
     * Mengembalikan kode asli untuk dikirim melalui email.
     */
    public function createOtp(User $user): string;

    /**
     * Memvalidasi OTP reset password.
     *
     * @return array{0: 'ok'|'not_found'|'expired'|'too_many_attempts'|'invalid', 1: int}
     */
    public function verifyOtp(User $user, string $code): array;

    /**
     * Menghitung sisa waktu sebelum user boleh meminta OTP baru.
     */
    public function secondsUntilResend(User $user): int;

    /**
     * Menghapus OTP milik user setelah password berhasil diubah.
     */
    public function deleteOtp(User $user): void;
}
