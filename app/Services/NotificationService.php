<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Helpers\AuthContextHelper;
use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseService<Notification>
 */
class NotificationService extends BaseService
{
    public function __construct(
        protected NotificationRepositoryInterface $notifRepository
    ) {
        parent::__construct($notifRepository);
    }

    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function getByUser(int $perPage = 10): LengthAwarePaginator
    {
        return $this->notifRepository->paginateByUser($this->currentUserId(), $perPage);
    }

    public function markAsRead(string $id): Notification
    {
        return $this->notifRepository->markAsRead($id, $this->currentUserId());
    }

    /**
     * @throws AuthException 401 jika belum login
     */
    private function currentUserId(): string
    {
        $id = AuthContextHelper::userLogin()->id();

        if ($id === null) {
            throw new AuthException('Unauthenticated.', 401);
        }

        return (string) $id;
    }
}
