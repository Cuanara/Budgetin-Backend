<?php

namespace App\Repositories\Contracts;

interface TransactionRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateByUser(string $userId, array $filters = [], int $perPage = 20);

    public function filterByUser(string $userId, array $filters = [], int $perPage = 20);
}
