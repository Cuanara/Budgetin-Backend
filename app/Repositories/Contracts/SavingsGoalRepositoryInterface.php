<?php

namespace App\Repositories\Contracts;

use App\Models\SavingsGoal;
use Illuminate\Database\Eloquent\Collection;

interface SavingsGoalRepositoryInterface
{
    public function getAllByUser(string $userId): Collection;

    public function create(array $data): SavingsGoal;

    public function findByUser(string $id, string $userId): SavingsGoal;

    public function update(SavingsGoal $savingsGoal, array $data): SavingsGoal;

    public function delete(SavingsGoal $savingsGoal): bool;
}
