<?php

namespace App\Services;

use App\Exceptions\TransactionException;
use App\Helpers\AuthContextHelper;
use App\Models\Category;
use App\Models\SavingsDeposit;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function createTransaction(array $data): Transaction
    {
        $userId = AuthContextHelper::userLogin()->id();
        $data['user_id'] = $userId;

        return DB::transaction(function () use ($data, $userId) {
            $walletId = $data['wallet_id'] ?? null;
            $wallet = null;

            if ($walletId) {
                $wallet = Wallet::where('id', $walletId)->where('user_id', $userId)->lockForUpdate()->first();
            }

            if (! $wallet) {
                $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            }

            if (! $wallet) {
                $wallet = Wallet::create([
                    'user_id' => $userId,
                    'name' => 'Main Wallet',
                    'balance' => 0,
                ]);
            }

            $data['wallet_id'] = $wallet->id;
            $amount = (float) $data['amount'];
            $type = $data['type'];

            if ($type === 'expense') {
                if ((float) $wallet->balance < $amount) {
                    throw new TransactionException('Saldo dompet tidak mencukupi untuk melakukan transaksi ini.', 422);
                }
                $wallet->decrement('balance', $amount);
            } elseif ($type === 'income') {
                $wallet->increment('balance', $amount);
            }

            $savingsGoal = null;
            if (! empty($data['savings_goal_id'])) {
                $savingsGoal = SavingsGoal::where('id', $data['savings_goal_id'])->where('user_id', $userId)->lockForUpdate()->first();
            } elseif (! empty($data['category_id'])) {
                $category = Category::find($data['category_id']);
                if ($category && (stripos($category->name, 'tabung') !== false || stripos($category->name, 'saving') !== false)) {
                    $savingsGoal = SavingsGoal::where('user_id', $userId)->where('status', 'active')->lockForUpdate()->first();
                }
            }

            if ($savingsGoal) {
                SavingsDeposit::create([
                    'savings_goal_id' => $savingsGoal->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'deposit_date' => $data['transaction_date'] ?? now()->toDateString(),
                    'notes' => $data['description'] ?? $data['notes'] ?? 'Menabung dari transaksi',
                ]);

                $savingsGoal->increment('current_amount', $amount);
                if ($savingsGoal->target_amount > 0 && $savingsGoal->current_amount >= $savingsGoal->target_amount) {
                    $savingsGoal->status = 'completed';
                    $savingsGoal->save();
                }
            }

            if (! empty($data['description']) && ! empty($data['notes']) && $data['description'] !== $data['notes']) {
                $data['notes'] = $data['description'] . ' - ' . $data['notes'];
            } elseif (! empty($data['description']) && empty($data['notes'])) {
                $data['notes'] = $data['description'];
            }

            unset($data['savings_goal_id']);

            return $this->transactionRepository->create($data);
        });
    }

    public function getUserTransactions(string $userId, array $filters = [], int $perPage = 20)
    {
        return $this->transactionRepository->filterByUser($userId, $filters, $perPage);
    }

    public function getFilteredTransactions(string $userId, array $filters = [], int $perPage = 20)
    {
        return $this->transactionRepository->filterByUser($userId, $filters, $perPage);
    }
}
