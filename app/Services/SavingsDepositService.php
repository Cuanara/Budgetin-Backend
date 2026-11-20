<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\SavingsGoal;
use App\Models\SavingsDeposit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsDepositService
{
    public function getAll(string $savingsGoalId)
    {
        $savingsGoal = SavingsGoal::where('user_id', Auth::id())
            ->findOrFail($savingsGoalId);

        return $savingsGoal->deposits()
            ->latest('deposit_date')
            ->get();
    }

    public function create(string $savingsGoalId, array $data)
    {
        return DB::transaction(function () use ($savingsGoalId, $data) {

            $savingsGoal = SavingsGoal::where('user_id', Auth::id())
                ->lockForUpdate()
                ->findOrFail($savingsGoalId);

            if ($savingsGoal->status !== 'active') {
                throw ValidationException::withMessages([
                    'savings_goal' => 'Tabungan ini tidak sedang aktif.',
                ]);
            }

            if (!empty($data['wallet_id'])) {
                $wallet = Wallet::where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->findOrFail($data['wallet_id']);

                if ((float) $wallet->balance < (float) $data['amount']) {
                    throw ValidationException::withMessages([
                        'amount' => 'Saldo Wallet tidak mencukupi.',
                    ]);
                }

                $wallet->decrement('balance', $data['amount']);
            }

            $deposit = $savingsGoal->deposits()->create($data);

            $savingsGoal->increment('current_amount', $data['amount']);
            $savingsGoal->refresh();

            if ((float) $savingsGoal->current_amount >= (float) $savingsGoal->target_amount) {
                $savingsGoal->update([
                    'status' => 'completed',
                ]);
            }

            return $deposit->load('wallet');
        });
    }
}
