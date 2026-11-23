<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateByUser(string $userId, array $filters = [], int $perPage = 20);

    public function filterByUser(string $userId, array $filters = [], int $perPage = 20);

    public function createWithWalletAndSavings(array $data, string $userId): Transaction;
}
