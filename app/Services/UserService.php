<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Helpers\AuthContextHelper;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * @extends BaseService<User>
 */
class UserService extends BaseService
{
    private const AVATAR_DISK = 'public';

    private const AVATAR_DIR = 'avatars';

    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
        parent::__construct($userRepository);
    }

    /**
     * @throws AuthException
     */
    public function delete(string $id): bool
    {
        $this->ensureOwnAccount($id);

        AuthContextHelper::userLogin()->logout();

        return parent::delete($id);
    }

    /**
     * @throws AuthException
     */
    private function ensureOwnAccount(string $id): void
    {
        if (AuthContextHelper::userLogin()->id() !== $id) {
            throw new AuthException('Akun tidak sesuai dengan yang login', 403);
        }
    }
}
