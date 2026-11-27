<?php

namespace App\Repositories;

use App\Models\Wishlist;
use App\Repositories\Contracts\WishlistRepositoryInterface;

class WishlistRepository extends BaseRepository implements WishlistRepositoryInterface
{
    protected function model(): string
    {
        return Wishlist::class;
    }

    /**
     * Iterasi wishlist berstatus delayed yang masa tundanya sudah habis.
     */
    public function chunkDelayEnded(string $date, int $size, callable $callback): bool
    {
        return $this->model->query()
            ->where('status', 'delayed')
            ->whereNotNull('delay_until')
            ->where('delay_until', '<=', $date)
            ->with('user')
            ->chunkById($size, $callback);
    }

    /**
     * Klaim atomik: hanya satu proses yang berhasil mengubah status.
     */
    public function claimPendingDecision(string $id): bool
    {
        return $this->model->query()
            ->whereKey($id)
            ->where('status', 'delayed')
            ->update(['status' => 'pending_decision']) > 0;
    }
}
