<?php

namespace App\Repositories\Contracts;

use App\Models\EmailVerificationOtp;
use App\Models\User;

/**
 * @extends BaseRepositoryInterface<EmailVerificationOtp>
 */
interface EmailVerificationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Buat OTP baru (OTP lama milik user dihapus).
     * Mengembalikan kode asli (belum di-hash) untuk dikirim ke email.
     */
    public function createOtp(User $user): string;

    /**
     * Cek OTP. Jika benar, email_verified_at diisi dan OTP dihapus.
     *
     * @return array{0: 'ok'|'not_found'|'expired'|'too_many_attempts'|'invalid', 1: int}
     *                                                                                    [status, sisa percobaan]
     */
    public function verifyOtp(User $user, string $code): array;

    /**
     * Sisa detik sebelum boleh kirim ulang OTP (0 = boleh sekarang).
     */
    public function secondsUntilResend(User $user): int;
}
