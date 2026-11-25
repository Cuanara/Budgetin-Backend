<?php

namespace App\Repositories\Contracts;

use App\Models\RefreshToken;
use App\Models\User;

/**
 * @extends BaseRepositoryInterface<User>
 *
 * @phpstan-import-type ClientMeta from \App\Services\AuthService
 */
interface AuthRepositoryInterface extends BaseRepositoryInterface
{
    // Login

    /**
     * @return array<string, mixed>|null
     */
    public function verifyIdToken(string $idToken): ?array;

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function validateEmailCredentials(array $credentials): ?User;

    public function loginWithUser(User $user): string;

    public function findByGoogleId(string $googleId): ?User;

    public function findByEmail(string $email): ?User;

    // Register

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createGoogleUser(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateGoogleData(User $user, array $attributes): User;

    // Refresh Token
    public function accessTokenTtl(): int;

    /**
     * @param  ClientMeta  $meta
     * @return array{string, RefreshToken}
     */
    public function createRefreshToken(User $user, bool $remember, array $meta = []): array;

    public function findRefreshTokenForUpdate(string $plainToken): ?RefreshToken;

    public function revokeRefreshToken(RefreshToken $token): void;

    public function revokeRefreshTokenByPlain(string $plainToken): void;

    public function revokeAllRefreshTokens(string $userId): int;

    public function invalidateAccessToken(): void;

    public function updateUserPassword(string $email, string $hashedPassword): void;

    public function markTokenAsVerified(string $email, string $verifiedToken): void;

    public function saveEmailVerificationOtp(string $email, string $hashedOtp): void;

    public function findEmailVerificationOtp(string $email): ?object;

    public function deleteEmailVerificationOtp(string $email): void;

    public function markEmailAsVerified(string $userId): void;
}
