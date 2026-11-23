<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;

    public function paginateByUser(string $userId, int $perPage = 20);

    public function filterByUser(string $userId, ?string $startDate, ?string $endDate, ?string $categoryId, int $perPage = 20);
}
