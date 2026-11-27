<?php

namespace App\Repositories;

use App\Models\Wishlist;
use App\Repositories\Contracts\WishlistRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class WishlistRepository extends BaseRepository implements WishlistRepositoryInterface
{
    protected function model(): string
    {
        return Wishlist::class;
    }

    public function syncExpiredWishlistsForUser(string $userId): void
    {
        $today = Carbon::today();

        $expiredWishlists = Wishlist::where('user_id', $userId)
            ->where('status', 'delayed')
            ->whereNotNull('delay_until')
            ->whereDate('delay_until', '<=', $today)
            ->get();

        foreach ($expiredWishlists as $wishlist) {
            if ((float) $wishlist->saved_amount >= (float) $wishlist->price) {
                $wishlist->status = 'pending_decision';
            } else {
                $wishlist->status = 'archived';
                $wishlist->delay_days = 0;
            }
            $wishlist->save();
        }
    }

    public function getAllByUser(string $userId, ?string $tab = null, ?string $sort = null): Collection
    {
        $query = Wishlist::where('user_id', $userId);

        if ($tab === 'active') {
            $query->whereIn('status', ['delayed', 'pending_decision']);
        } elseif ($tab === 'archived') {
            $query->where('status', 'archived');
        } elseif ($tab === 'bought') {
            $query->where('status', 'bought');
        }

        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'delay_asc' => $query->orderByRaw('CASE WHEN delay_until IS NULL THEN 1 ELSE 0 END, delay_until ASC'),
            'delay_desc' => $query->orderByRaw('CASE WHEN delay_until IS NULL THEN 1 ELSE 0 END, delay_until DESC'),
            'progress_desc' => $query->orderByRaw('CASE WHEN price > 0 THEN (saved_amount / price) ELSE 0 END DESC'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->get();
    }

    public function getTotalSavedAmountByUser(string $userId): float
    {
        return (float) Wishlist::where('user_id', $userId)
            ->whereIn('status', ['delayed', 'pending_decision', 'archived'])
            ->sum('saved_amount');
    }

    public function findByUser(string $id, string $userId): Wishlist
    {
        return Wishlist::where('user_id', $userId)->findOrFail($id);
    }

    public function findByUserForUpdate(string $id, string $userId): Wishlist
    {
        return Wishlist::where('user_id', $userId)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create(array $data): Wishlist
    {
        return Wishlist::create($data);
    }

    public function updateWishlist(Wishlist $wishlist, array $data): Wishlist
    {
        $wishlist->update($data);

        return $wishlist->refresh();
    }

    public function deleteWishlist(Wishlist $wishlist): bool
    {
        return (bool) $wishlist->delete();
    }
}

