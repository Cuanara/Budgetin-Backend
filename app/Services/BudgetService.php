<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Repositories\Contracts\BudgetRepositoryInterface;

class BudgetService extends BaseService
{
    public function __construct(
        protected BudgetRepositoryInterface $budgetRepository
    ) {
        parent::__construct($budgetRepository);
    }

    public function getBudget(string $userId, ?int $month = null, ?int $year = null): Budget
    {
        $month = $month ?: (int) now()->format('n');
        $year = $year ?: (int) now()->format('Y');

        return $this->budgetRepository->getBudgetWithAllocations($userId, $month, $year);
    }

    public function addAllocation(string $userId, array $data): BudgetAllocation
    {
        return $this->budgetRepository->addAllocation($userId, $data);
    }

    public function deleteAllocation(string $userId, string $allocationId): bool
    {
        return $this->budgetRepository->deleteAllocation($userId, $allocationId);
    }

    public function checkRollover(string $userId, ?int $month = null, ?int $year = null): bool
    {
        return $this->budgetRepository->checkRollover($userId, $month, $year);
    }

    public function processRollover(string $userId, array $data): Budget
    {
        return $this->budgetRepository->processRollover($userId, $data);
    }
}
