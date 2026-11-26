<?php

namespace App\Services;

use App\Models\SavingsDeposit;
use App\Repositories\Contracts\SavingsDepositRepositoryInterface;
use App\Repositories\Contracts\SavingsGoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsDepositService
{
    protected SavingsDepositRepositoryInterface $savingsDepositRepository;
    protected SavingsGoalRepositoryInterface $savingsGoalRepository;

    public function __construct(
        SavingsDepositRepositoryInterface $savingsDepositRepository,
        SavingsGoalRepositoryInterface $savingsGoalRepository
    ) {
        $this->savingsDepositRepository = $savingsDepositRepository;
        $this->savingsGoalRepository = $savingsGoalRepository;
    }

    public function getAllBySavingsGoal(string $savingsGoalId): Collection
    {
        $savingsGoal = $this->savingsGoalRepository->findByUser(
            $savingsGoalId,
            (string) Auth::guard('api')->id()
        );

        return $this->savingsDepositRepository
            ->getAllBySavingsGoal($savingsGoal);
    }

    public function create(
        string $savingsGoalId,
        array $data
    ): SavingsDeposit {
        return DB::transaction(function () use ($savingsGoalId, $data) {
            $userId = (string) Auth::guard('api')->id();

            $savingsGoal = $this->savingsDepositRepository
                ->findSavingsGoalForUpdate($savingsGoalId, $userId);

            if ($savingsGoal->status !== 'active') {
                throw ValidationException::withMessages([
                    'savings_goal' => 'Target tabungan tidak sedang aktif.',
                ]);
            }

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal deposit harus lebih dari 0.',
                ]);
            }

            $remainingAmount =
                (float) $savingsGoal->target_amount
                - (float) $savingsGoal->current_amount;

            if ($amount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal deposit melebihi sisa target tabungan.',
                ]);
            }

            $data['amount'] = $amount;

            $deposit = $this->savingsDepositRepository
                ->create($savingsGoal, $data);

            $updatedSavingsGoal = $this->savingsDepositRepository
                ->increaseSavingsGoalAmount($savingsGoal, $amount);

            if (
                (float) $updatedSavingsGoal->current_amount
                >= (float) $updatedSavingsGoal->target_amount
            ) {
                $this->savingsDepositRepository
                    ->completeSavingsGoal($updatedSavingsGoal);
            }

            return $deposit;
        });
    }
}
