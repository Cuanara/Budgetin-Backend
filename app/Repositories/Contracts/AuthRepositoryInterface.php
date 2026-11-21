<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function verifyIdToken(string $idToken): ?array;

    public function loginWithUser(User $user): string;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function loginEmail(array $credentials): ?string;

    public function logout(): bool;

    public function refreshToken(): string;

    public function findByGoogleId(string $googleId): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createGoogleUser(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateGoogleData(User $user, array $attributes): User;

    public function updateActiveStatus(string $userId, bool $isActive): int;

    public function saveResetToken(string $email, string $hashedOtp): void;

    public function deleteResetToken(string $email): void;

    public function updateUserPassword(string $email, string $hashedPassword): void;

    public function markTokenAsVerified(string $email, string $verifiedToken): void;

    public function saveEmailVerificationOtp(string $email, string $hashedOtp): void;

    public function findEmailVerificationOtp(string $email): ?object;

    public function deleteEmailVerificationOtp(string $email): void;

    public function markEmailAsVerified(string $userId): void;
}
