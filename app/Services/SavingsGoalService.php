<?php

namespace App\Services;

use App\Models\SavingsGoal;
use App\Repositories\Contracts\SavingsGoalRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class SavingsGoalService
{
    protected SavingsGoalRepositoryInterface $savingsGoalRepository;

    public function __construct(
        SavingsGoalRepositoryInterface $savingsGoalRepository
    ) {
        $this->savingsGoalRepository = $savingsGoalRepository;
    }

    public function getAll()
    {
        return $this->savingsGoalRepository->getAllByUser(
            (string) Auth::guard('api')->id()
        );
    }

    public function create(array $data): SavingsGoal
    {
        $data['user_id'] = Auth::guard('api')->id();
        $data['current_amount'] = 0;
        $data['status'] = 'active';

        return $this->savingsGoalRepository->create($data);
    }

    public function getById(string $id): SavingsGoal
    {
        return $this->savingsGoalRepository->findByUser(
            $id,
            (string) Auth::guard('api')->id()
        );
    }

    public function update(string $id, array $data): SavingsGoal
    {
        $savingsGoal = $this->getById($id);

        return $this->savingsGoalRepository->update(
            $savingsGoal,
            $data
        );
    }

    public function delete(string $id): bool
    {
        $savingsGoal = $this->getById($id);

        return $this->savingsGoalRepository->delete($savingsGoal);
    }
}
