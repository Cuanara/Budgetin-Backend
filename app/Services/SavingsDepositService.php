<?php

namespace App\Services;

use App\Models\SavingsDeposit;
use App\Repositories\Contracts\SavingsDepositRepositoryInterface;
use App\Repositories\Contracts\SavingsGoalRepositoryInterface;
use App\Repositories\Contracts\WishlistRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsDepositService
{
    protected SavingsDepositRepositoryInterface $savingsDepositRepository;

    protected SavingsGoalRepositoryInterface $savingsGoalRepository;
    protected WishlistRepositoryInterface $wishlistRepository;

    public function __construct(
        SavingsDepositRepositoryInterface $savingsDepositRepository,
        SavingsGoalRepositoryInterface $savingsGoalRepository,
        WishlistRepositoryInterface $wishlistRepository
    ) {
        $this->savingsDepositRepository = $savingsDepositRepository;
        $this->savingsGoalRepository = $savingsGoalRepository;
        $this->wishlistRepository = $wishlistRepository;
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

            $wishlistId = $data['wishlist_id'] ?? null;
            unset($data['wishlist_id']);

            if ($wishlistId !== null) {
                $wishlist = $this->wishlistRepository->findByUserForUpdate($wishlistId, $userId);

                if (in_array($wishlist->status, ['bought', 'cancelled'], true)) {
                    throw ValidationException::withMessages([
                        'wishlist_id' => 'Wishlist ini sudah dibeli atau dibatalkan.',
                    ]);
                }

                $newSavedAmount = (float) $wishlist->saved_amount + $amount;
                $wishlistUpdate = ['saved_amount' => $newSavedAmount];

                if (
                    $wishlist->status === 'delayed'
                    && $wishlist->delay_until !== null
                    && Carbon::today()->gte($wishlist->delay_until->startOfDay())
                    && $newSavedAmount >= (float) $wishlist->price
                ) {
                    $wishlistUpdate['status'] = 'pending_decision';
                }

                $this->wishlistRepository->updateWishlist($wishlist, $wishlistUpdate);

                if (empty($data['notes'])) {
                    $data['notes'] = 'Setoran Wishlist: ' . $wishlist->item_name;
                }
            } else {
                $remainingAmount =
                    (float) $savingsGoal->target_amount
                    - (float) $savingsGoal->current_amount;

                if ($amount > $remainingAmount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal deposit melebihi sisa target tabungan.',
                    ]);
                }
            }

            $data['amount'] = $amount;

            $deposit = $this->savingsDepositRepository
                ->create($savingsGoal, $data);

            $updatedSavingsGoal = $this->savingsDepositRepository
                ->increaseSavingsGoalAmount($savingsGoal, $amount);

            if (
                $wishlistId === null
                && (float) $updatedSavingsGoal->current_amount
                >= (float) $updatedSavingsGoal->target_amount
            ) {
                $this->savingsDepositRepository
                    ->completeSavingsGoal($updatedSavingsGoal);
            }

            return $deposit;
        });
    }
}
