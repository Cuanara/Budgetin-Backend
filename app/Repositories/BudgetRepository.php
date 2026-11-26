<?php

namespace App\Repositories;

use App\Exceptions\BudgetException;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\BudgetRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BudgetRepository extends BaseRepository implements BudgetRepositoryInterface
{
    protected function model(): string
    {
        return Budget::class;
    }

    protected function getMainWalletBalance(string $userId): float
    {
        $wallet = Wallet::where('user_id', $userId)
            ->where('name', 'Main Wallet')
            ->first();

        if (! $wallet) {
            $wallet = Wallet::where('user_id', $userId)
                ->orderBy('created_at')
                ->first();
        }

        return $wallet ? (float) $wallet->balance : 0.0;
    }

    public function getOrCreateBudget(string $userId, int $month, int $year): Budget
    {
        $mainWalletBalance = $this->getMainWalletBalance($userId);

        $budget = Budget::where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (! $budget) {
            $budget = Budget::create([
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
                'total_income' => $mainWalletBalance,
            ]);
        } elseif ((float) $budget->total_income !== $mainWalletBalance) {
            $budget->total_income = $mainWalletBalance;
            $budget->save();
        }

        return $budget;
    }

    public function getBudgetWithAllocations(string $userId, int $month, int $year): Budget
    {
        $budget = $this->getOrCreateBudget($userId, $month, $year);
        $mainWalletBalance = (float) $budget->total_income;

        $budget->load(['allocations.category']);

        foreach ($budget->allocations as $allocation) {
            $spent = 0;
            if ($allocation->category_id) {
                $spent = (float) Transaction::where('user_id', $userId)
                    ->where('type', 'expense')
                    ->where('category_id', $allocation->category_id)
                    ->whereYear('transaction_date', $year)
                    ->whereMonth('transaction_date', $month)
                    ->sum('amount');
            }
            $allocation->spent_amount = $spent;
            $allocation->allocated_amount = ((float) $allocation->percentage / 100) * $mainWalletBalance;
        }

        return $budget;
    }

    public function addAllocation(string $userId, array $data): BudgetAllocation
    {
        return DB::transaction(function () use ($userId, $data) {
            $month = isset($data['month']) ? (int) $data['month'] : (int) now()->format('n');
            $year = isset($data['year']) ? (int) $data['year'] : (int) now()->format('Y');

            $budget = $this->getOrCreateBudget($userId, $month, $year);

            $categoryId = $data['category_id'] ?? null;
            if (! $categoryId && ! empty($data['name'])) {
                $category = Category::where('user_id', $userId)
                    ->where('name', $data['name'])
                    ->first();

                if (! $category) {
                    $category = Category::create([
                        'user_id' => $userId,
                        'name' => $data['name'],
                        'type' => 'expense',
                        'color' => '#10B981',
                    ]);
                }
                $categoryId = $category->id;
            }

            if ($categoryId) {
                $exists = BudgetAllocation::where('budget_id', $budget->id)
                    ->where('category_id', $categoryId)
                    ->exists();

                if ($exists) {
                    throw new BudgetException('Kategori ini sudah ada dalam alokasi budget bulan ini.', 422);
                }
            }

            $currentTotal = (float) BudgetAllocation::where('budget_id', $budget->id)->sum('percentage');
            $newPercentage = (float) $data['percentage'];

            if ($currentTotal + $newPercentage > 100) {
                $sourceAllocation = null;

                if (! empty($data['source_allocation_id'])) {
                    $sourceAllocation = BudgetAllocation::where('id', $data['source_allocation_id'])
                        ->where('budget_id', $budget->id)
                        ->lockForUpdate()
                        ->first();
                } elseif (! empty($data['source_category_id'])) {
                    $sourceAllocation = BudgetAllocation::where('category_id', $data['source_category_id'])
                        ->where('budget_id', $budget->id)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $sourceAllocation) {
                    throw new BudgetException('Total jatah alokasi melebihi 100%. Silakan pilih kategori sumber untuk dikurangi jatahnya.', 422);
                }

                if ((float) $sourceAllocation->percentage < $newPercentage) {
                    throw new BudgetException('Jatah kategori sumber tidak mencukupi untuk dipotong ' . $newPercentage . '%.', 422);
                }

                $sourceAllocation->percentage = (float) $sourceAllocation->percentage - $newPercentage;
                $sourceAllocation->allocated_amount = ((float) $sourceAllocation->percentage / 100) * (float) $budget->total_income;
                $sourceAllocation->save();
            }

            $allocatedAmount = ($newPercentage / 100) * (float) $budget->total_income;

            return BudgetAllocation::create([
                'budget_id' => $budget->id,
                'category_id' => $categoryId,
                'custom_name' => $data['name'] ?? null,
                'percentage' => $newPercentage,
                'allocated_amount' => $allocatedAmount,
            ]);
        });
    }

    public function deleteAllocation(string $userId, string $allocationId): bool
    {
        $allocation = BudgetAllocation::where('id', $allocationId)
            ->whereHas('budget', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->first();

        if (! $allocation) {
            throw new BudgetException('Data alokasi budget tidak ditemukan.', 404);
        }

        return (bool) $allocation->delete();
    }

    /**
     * Check if the total allocation percentage for the given month/year exceeds 100%.
     * Returns true if within limit (<=100), false otherwise.
     */
    public function checkRollover(string $userId, ?int $month = null, ?int $year = null): bool
    {
        $month = $month ?: (int) now()->format('n');
        $year = $year ?: (int) now()->format('Y');

        $budget = $this->getOrCreateBudget($userId, $month, $year);
        $total = (float) BudgetAllocation::where('budget_id', $budget->id)->sum('percentage');
        return $total <= 100;
    }

    /**
     * Process rollover – for now simply ensure allocations are recalculated based on current total income.
     * Returns the refreshed Budget model.
     */
    public function processRollover(string $userId, array $data): Budget
    {
        $month = $data['month'] ?? (int) now()->format('n');
        $year = $data['year'] ?? (int) now()->format('Y');
        $budget = $this->getOrCreateBudget($userId, $month, $year);

        // Re-calculate allocated_amount for each allocation based on current total_income
        foreach ($budget->allocations as $allocation) {
            $allocation->allocated_amount = ((float) $allocation->percentage / 100) * (float) $budget->total_income;
            $allocation->save();
        }

        // Return fresh budget with allocations loaded
        return $this->getBudgetWithAllocations($userId, $month, $year);
    }
}