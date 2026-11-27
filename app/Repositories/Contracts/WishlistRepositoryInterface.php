<?php

namespace App\Repositories\Contracts;

use App\Models\SavingsGoal;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

interface WishlistRepositoryInterface
{
    public function getAllByUser(string $userId, ?string $tab = null, ?string $sort = null): Collection;

    public function getTotalSavedAmountByUser(string $userId): float;

    public function syncExpiredWishlistsForUser(string $userId): void;

    public function findByUser(string $id, string $userId): Wishlist;

    public function findByUserForUpdate(string $id, string $userId): Wishlist;

    public function create(array $data): Wishlist;

    public function updateWishlist(Wishlist $wishlist, array $data): Wishlist;

    public function deleteWishlist(Wishlist $wishlist): bool;

    public function findOrCreateWishlistSavingsGoal(string $userId): SavingsGoal;

    public function deductSavingsGoalAmount(string $savingsGoalId, string $userId, float $amount): void;
}

