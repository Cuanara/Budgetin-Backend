<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Notifications\WishlistDelayEnded;
use App\Repositories\WishlistRepository;

/**
 * @extends BaseService<Wishlist>
 */
class WishlistService extends BaseService
{
    public function __construct(
        private readonly WishlistRepository $wishlists,
    ) {}

    public function notifyEndedDelays(): int
    {
        $today = now('Asia/Jakarta')->toDateString();
        $sent = 0;

        $this->wishlists->chunkDelayEnded($today, 200, function ($wishlists) use (&$sent) {
            foreach ($wishlists as $wishlist) {
                $claimed = $this->wishlists->claimPendingDecision($wishlist->id);

                if (! $claimed || ! $wishlist->user) {
                    continue;
                }

                $wishlist->status = 'pending_decision';
                $wishlist->user->notify(new WishlistDelayEnded($wishlist));
                $sent++;
            }
        });

        return $sent;
    }
}
