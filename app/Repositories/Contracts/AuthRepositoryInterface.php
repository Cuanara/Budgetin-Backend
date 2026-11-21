<?php

namespace App\Repositories\Contracts;

interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    public function createOrUpdateGoogleUser(array $googleData);

    public function login(array $credentials): ?string;

    public function logout($request = null): bool;

    public function refreshToken(): string;

    public function findUserByEmail(string $email);

    public function updateActiveStatus(string $userId, bool $isActive);

    public function findResetToken(string $email);

    public function saveResetToken(string $email, string $hashedOtp): void;

    public function deleteResetToken(string $email): void;

    public function updateUserPassword(string $email, string $hashedPassword): void;

    public function markTokenAsVerified(string $email, string $verifiedToken): void;

    public function findByVerifiedToken(string $verifiedToken);

    public function saveEmailVerificationOtp(string $email, string $hashedOtp): void;

    public function findEmailVerificationOtp(string $email);

    public function deleteEmailVerificationOtp(string $email): void;

    public function markEmailAsVerified(string $userId): void;
}
