<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::guard('api')->id();

            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw ValidationException::withMessages([
                    'wallet' => 'Wallet pengguna belum tersedia.',
                ]);
            }

            $amount = (int) round((float) $data['amount'] * 100);
            $balance = (int) round((float) $wallet->balance * 100);

            if ($data['type'] === 'income') {
                $newBalance = $balance + $amount;
            } else {
                if ($balance < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Saldo Wallet tidak mencukupi.',
                    ]);
                }

                $newBalance = $balance - $amount;
            }

            $data['user_id'] = $userId;
            $data['wallet_id'] = $wallet->id;

            // Database menggunakan kolom description.
            $transaction = $this->transactionRepository->create($data);

            $wallet->update([
                'balance' => number_format(
                    $newBalance / 100,
                    2,
                    '.',
                    ''
                ),
            ]);

            return $transaction;
        });
    }
}
