<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function getDashboardData(string $userId, ?int $month = null, ?int $year = null): array
    {
        $now = Carbon::now();
        $targetMonth = $month ?? (int) $now->month;
        $targetYear = $year ?? (int) $now->year;

        $mainWallet = $this->dashboardRepository->getMainWallet($userId);
        $currentBalance = $mainWallet ? (float) $mainWallet->balance : 0.0;

        $currentSavingsAmount = $this->dashboardRepository->getTotalCurrentSavings($userId);
        $totalWishlistPrice = $this->dashboardRepository->getTotalActiveWishlistPrice($userId);
        $remainingForWishlist = max(0.0, $totalWishlistPrice - $currentSavingsAmount);

        $monthlyExpenseTotal = $this->dashboardRepository->getMonthlyExpenseTotal($userId, $targetMonth, $targetYear);
        $expensesByCategory = $this->dashboardRepository->getMonthlyExpensesByCategory($userId, $targetMonth, $targetYear);
        $budgetBasis = $this->dashboardRepository->getMonthlyBudgetBasis($userId, $targetMonth, $targetYear);

        $percentageUsed = $budgetBasis > 0
            ? round(($monthlyExpenseTotal / $budgetBasis) * 100, 1)
            : 0.0;

        $categories = $expensesByCategory->map(function ($item) use ($monthlyExpenseTotal) {
            $categoryTotal = (float) $item->total_amount;
            $sharePercentage = $monthlyExpenseTotal > 0
                ? round(($categoryTotal / $monthlyExpenseTotal) * 100, 1)
                : 0.0;

            return [
                'category_id' => $item->category_id,
                'name' => $item->category?->name ?? 'Lainnya',
                'color' => $item->category?->color,
                'total' => $categoryTotal,
                'percentage' => $sharePercentage,
            ];
        })->values()->all();

        $recentTransactions = $this->dashboardRepository->getRecentTransactions($userId, 5)
            ->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'title' => $trx->notes ?: ($trx->category?->name ?? ucfirst($trx->type)),
                    'category_name' => $trx->category?->name ?? 'Tanpa Kategori',
                    'type' => $trx->type,
                    'amount' => (float) $trx->amount,
                    'transaction_date' => optional($trx->transaction_date)->format('Y-m-d'),
                ];
            })->values()->all();

        return [
            'current_balance' => [
                'amount' => $currentBalance,
            ],
            'current_savings' => [
                'amount' => $currentSavingsAmount,
                'total_wishlist_price' => $totalWishlistPrice,
                'remaining_for_wishlist' => $remainingForWishlist,
            ],
            'monthly_expense' => [
                'total' => $monthlyExpenseTotal,
                'percentage_of_budget' => $percentageUsed,
                'by_category' => $categories,
            ],
            'expense_summary' => [
                'percentage_used' => $percentageUsed,
                'spent_amount' => $monthlyExpenseTotal,
                'budget_amount' => $budgetBasis,
            ],
            'recent_transactions' => $recentTransactions,
        ];
    }
}