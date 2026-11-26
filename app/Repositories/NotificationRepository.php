<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Notification>
 */
class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    protected function model(): string
    {
        return Notification::class;
    }

    public function paginateByUser(string $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function markAsRead(string $id, string $userId): Notification
    {
        // Di-scope ke user, jadi notifikasi milik user lain akan 404
        $notification = $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->refresh();
    }
}
