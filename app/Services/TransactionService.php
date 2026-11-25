<?php

namespace App\Services;

use App\Helpers\AuthContextHelper;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionService extends BaseService
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {
        parent::__construct($transactionRepository);
    }

    public function createTransaction(array $data): Transaction
    {
        $userId = AuthContextHelper::userLogin()->id();

        return $this->transactionRepository->createWithWalletAndSavings($data, $userId);
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
