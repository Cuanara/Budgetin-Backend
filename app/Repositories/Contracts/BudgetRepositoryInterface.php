<?php

namespace App\Repositories\Contracts;

use App\Models\Budget;
use App\Models\BudgetAllocation;

interface BudgetRepositoryInterface
{
    public function getOrCreateBudget(string $userId, int $month, int $year): Budget;

    public function getBudgetWithAllocations(string $userId, int $month, int $year): Budget;

    public function addAllocation(string $userId, array $data): BudgetAllocation;

    public function deleteAllocation(string $userId, string $allocationId): bool;

    /**
     * Check if total allocation percentage exceeds 100% for the given month/year.
     * Returns true if within limit, false otherwise.
     */
    public function checkRollover(string $userId, ?int $month = null, ?int $year = null): bool;

    /**
     * Process a rollover action (e.g., reset allocations, adjust percentages).
     * For now this simply returns the budget after ensuring allocations are within limits.
     */
    public function processRollover(string $userId, array $data): Budget;
}
