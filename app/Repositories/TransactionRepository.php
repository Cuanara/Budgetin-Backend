<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository extends BaseRepository implements TransactionRepositoryInterface
{
    protected function model(): string
    {
        return Transaction::class;
    }

    public function create(array $data): Transaction
    {
        return $this->model->newQuery()->create($data);
    }

    public function paginateByUser(string $userId, array $filters = [], int $perPage = 20)
    {
        return $this->filterByUser($userId, $filters, $perPage);
    }

    public function filterByUser(string $userId, array $filters = [], int $perPage = 20)
    {
        $query = $this->model->newQuery()->where('user_id', $userId);

        if (!empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderByDesc('transaction_date')->paginate($perPage);
    }
}
