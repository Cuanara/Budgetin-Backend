<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Wishlist;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Collection;

class DashboardRepository extends BaseRepository implements DashboardRepositoryInterface
{
    public function model(): string
    {
        return Transaction::class;
    }

    public function getMainWallet(string $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('name', 'Main Wallet')
            ->first()
            ?? Wallet::where('user_id', $userId)->first();
    }

    public function getMonthlyExpensesByCategory(string $userId, int $month, int $year): Collection
    {
        return Transaction::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->get();
    }

    public function getMonthlyExpenseTotal(string $userId, int $month, int $year): float
    {
        return (float) Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->sum('amount');
    }

    public function getMonthlyBudgetBasis(string $userId, int $month, int $year): float
    {
        $wallet = $this->getMainWallet($userId);

        if ($wallet !== null) {
            return (float) $wallet->balance;
        }

        $budget = Budget::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return $budget ? (float) $budget->total_income : 0.0;
    }

    public function getRecentTransactions(string $userId, int $limit = 5): Collection
    {
        return Transaction::with('category')
            ->where('user_id', $userId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getTotalCurrentSavings(string $userId): float
    {
        return (float) SavingsGoal::where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->sum('current_amount');
    }

    public function getTotalActiveWishlistPrice(string $userId): float
    {
        return (float) Wishlist::where('user_id', $userId)
            ->whereIn('status', ['delayed', 'pending_decision'])
            ->sum('price');
    }
}