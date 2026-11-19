<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    public function createOrUpdateGoogleUser(array $googleData);

    public function generateTokenForUser(User $user): ?array;

    public function login(array $credentials): ?array;

    public function register(array $data);

    public function logout($request = null): bool;

    public function refreshToken(): array;

    public function getAuthenticatedUser(): array;

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
