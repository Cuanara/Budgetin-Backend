<?php

namespace App\Repositories\Contracts;

use App\Models\SavingsDeposit;
use App\Models\SavingsGoal;
use Illuminate\Database\Eloquent\Collection;

interface SavingsDepositRepositoryInterface
{
    public function getAllBySavingsGoal(SavingsGoal $savingsGoal): Collection;

    public function findSavingsGoalForUpdate(
        string $id,
        string $userId
    ): SavingsGoal;

    public function create(
        SavingsGoal $savingsGoal,
        array $data
    ): SavingsDeposit;

    public function increaseSavingsGoalAmount(
        SavingsGoal $savingsGoal,
        float $amount
    ): SavingsGoal;

    public function completeSavingsGoal(
        SavingsGoal $savingsGoal
    ): SavingsGoal;
}
