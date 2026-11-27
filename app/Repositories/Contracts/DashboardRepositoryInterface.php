<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;
use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    public function getMainWallet(string $userId): ?Wallet;

    public function getMonthlyExpensesByCategory(string $userId, int $month, int $year): Collection;

    public function getMonthlyExpenseTotal(string $userId, int $month, int $year): float;

    public function getMonthlyBudgetBasis(string $userId, int $month, int $year): float;

    public function getRecentTransactions(string $userId, int $limit = 5): Collection;

    public function getTotalCurrentSavings(string $userId): float;

    public function getTotalActiveWishlistPrice(string $userId): float;
}