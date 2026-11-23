<?php

namespace App\Services;

use App\Helpers\AuthContextHelper;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionService
{
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function createTransaction(array $data): Transaction
    {
        $data['user_id'] = AuthContextHelper::userLogin()->id();

        return $this->transactionRepository->create($data);
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
