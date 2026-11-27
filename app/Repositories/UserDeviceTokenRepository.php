<?php

namespace App\Repositories;

use App\Models\UserDeviceToken;
use App\Repositories\Contracts\UserDeviceTokenRepositoryInterface;

class UserDeviceTokenRepository extends BaseRepository implements UserDeviceTokenRepositoryInterface
{
    protected function model(): string
    {
        return UserDeviceToken::class;
    }

    public function deleteByToken(string $token): int
    {
        return $this->model->query()
            ->where('token', $token)
            ->delete();
    }
}
