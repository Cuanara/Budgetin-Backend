<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Helpers\AuthContextHelper;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * @extends BaseService<User>
 */
class UserService extends BaseService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
        parent::__construct($userRepository);
    }

    /**
     * Memperbarui profil pengguna.
     *
     * @param array<string, mixed> $data
     * @throws AuthException
     */
    public function updateProfile(string $id, array $data): User
    {
        $this->ensureOwnAccount($id);

        $user = $this->userRepository->findOrFail($id);
        $oldAvatar = $user->avatar;
        $newAvatarPath = null;

        if (array_key_exists('avatar', $data)) {
            $avatar = $data['avatar'];
            unset($data['avatar']);

            if ($avatar instanceof UploadedFile) {
                $newAvatarPath = $avatar->store(
                    self::AVATAR_DIR,
                    self::AVATAR_DISK
                );

                $data['avatar'] = $newAvatarPath;
            } elseif ($avatar === null) {
                $data['avatar'] = null;
            }
        }

        try {
            $updatedUser = $this->userRepository->update($id, $data);
        } catch (Throwable $e) {
            if ($newAvatarPath !== null) {
                Storage::disk(self::AVATAR_DISK)->delete($newAvatarPath);
            }

            throw $e;
        }

        if (
            array_key_exists('avatar', $data) &&
            $oldAvatar &&
            $oldAvatar !== $updatedUser->avatar
        ) {
            Storage::disk(self::AVATAR_DISK)->delete($oldAvatar);
        }

        /** @var User $updatedUser */
        return $updatedUser;
    }

    /**
     * @throws AuthException
     */
    public function delete(string $id): bool
    {
        $this->ensureOwnAccount($id);

        AuthContextHelper::userLogin()->logout();

        return $this->userRepository->delete($id);
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
