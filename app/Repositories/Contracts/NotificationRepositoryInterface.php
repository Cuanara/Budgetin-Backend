<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<Notification>
 */
interface NotificationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function paginateByUser(string $userId, int $perPage = 10): LengthAwarePaginator;

    public function markAsRead(string $id, string $userId): Notification;
}
