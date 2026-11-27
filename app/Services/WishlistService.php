<?php

namespace App\Services;

use App\Models\SavingsGoal;
use App\Models\Wishlist;
use App\Repositories\Contracts\WishlistRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    public function __construct(
        protected WishlistRepositoryInterface $wishlistRepository
    ) {}

    public function getAll(?string $tab = null, ?string $sort = null): array
    {
        $userId = (string) Auth::guard('api')->id();

        $this->wishlistRepository->syncExpiredWishlistsForUser($userId);

        $wishlists = $this->wishlistRepository->getAllByUser($userId, $tab, $sort);
        $totalWishlistSavings = $this->wishlistRepository->getTotalSavedAmountByUser($userId);

        return [
            'items' => $wishlists,
            'total_wishlist_savings' => $totalWishlistSavings,
        ];
    }

    public function getById(string $id): Wishlist
    {
        $userId = (string) Auth::guard('api')->id();

        $this->wishlistRepository->syncExpiredWishlistsForUser($userId);

        return $this->wishlistRepository->findByUser($id, $userId);
    }

    public function create(array $data): Wishlist
    {
        $userId = (string) Auth::guard('api')->id();
        $delayDays = (int) ($data['delay_days'] ?? 7);

        if (empty($data['savings_goal_id'])) {
            $wishlistGoal = SavingsGoal::firstOrCreate(
                ['user_id' => $userId, 'name' => 'Wishlist'],
                [
                    'target_amount' => 0,
                    'current_amount' => 0,
                    'status' => 'active',
                ]
            );
            $data['savings_goal_id'] = $wishlistGoal->id;
        }

        $data['user_id'] = $userId;
        $data['saved_amount'] = 0;
        $data['delay_days'] = $delayDays;
        $data['delay_until'] = Carbon::today()->addDays($delayDays)->toDateString();
        $data['status'] = 'delayed';

        return $this->wishlistRepository->create($data);
    }

    public function delete(string $id): bool
    {
        $wishlist = $this->getById($id);

        return $this->wishlistRepository->deleteWishlist($wishlist);
    }

    public function archive(string $id): Wishlist
    {
        return DB::transaction(function () use ($id) {
            $userId = (string) Auth::guard('api')->id();
            $wishlist = $this->wishlistRepository->findByUserForUpdate($id, $userId);

            if (in_array($wishlist->status, ['bought', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'wishlist' => 'Wishlist yang sudah dibeli atau dibatalkan tidak dapat diarsipkan.',
                ]);
            }

            if ($wishlist->status === 'archived') {
                return $wishlist;
            }

            $remainingDays = 0;
            if ($wishlist->delay_until !== null) {
                $remainingDays = max(0, (int) Carbon::today()->diffInDays($wishlist->delay_until->startOfDay(), false));
            }

            return $this->wishlistRepository->updateWishlist($wishlist, [
                'status' => 'archived',
                'delay_days' => $remainingDays,
            ]);
        });
    }

    public function unarchive(string $id, ?int $delayDays = null): Wishlist
    {
        return DB::transaction(function () use ($id, $delayDays) {
            $userId = (string) Auth::guard('api')->id();
            $wishlist = $this->wishlistRepository->findByUserForUpdate($id, $userId);

            if ($wishlist->status !== 'archived') {
                throw ValidationException::withMessages([
                    'wishlist' => 'Hanya wishlist yang diarsipkan yang dapat diaktifkan kembali.',
                ]);
            }

            $days = $delayDays ?? ($wishlist->delay_days > 0 ? (int) $wishlist->delay_days : 7);

            return $this->wishlistRepository->updateWishlist($wishlist, [
                'status' => 'delayed',
                'delay_days' => $days,
                'delay_until' => Carbon::today()->addDays($days)->toDateString(),
            ]);
        });
    }

    public function confirmPurchase(string $id, bool $purchased): Wishlist
    {
        return DB::transaction(function () use ($id, $purchased) {
            $userId = (string) Auth::guard('api')->id();
            $this->wishlistRepository->syncExpiredWishlistsForUser($userId);

            $wishlist = $this->wishlistRepository->findByUserForUpdate($id, $userId);

            if ($wishlist->status !== 'pending_decision') {
                throw ValidationException::withMessages([
                    'wishlist' => 'Wishlist belum siap untuk keputusan pembelian. Pastikan masa tunda telah selesai dan saldo mencukupi.',
                ]);
            }

            if ($purchased) {
                if ((float) $wishlist->saved_amount < (float) $wishlist->price) {
                    throw ValidationException::withMessages([
                        'saved_amount' => 'Saldo tabungan wishlist tidak mencukupi untuk membeli barang ini.',
                    ]);
                }

                $remainingBalance = max(0.0, (float) $wishlist->saved_amount - (float) $wishlist->price);

                if ($wishlist->savings_goal_id) {
                    $savingsGoal = SavingsGoal::where('id', $wishlist->savings_goal_id)
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->first();

                    if ($savingsGoal) {
                        $savingsGoal->current_amount = max(0.0, (float) $savingsGoal->current_amount - (float) $wishlist->price);
                        $savingsGoal->save();
                    }
                }

                return $this->wishlistRepository->updateWishlist($wishlist, [
                    'saved_amount' => $remainingBalance,
                    'status' => 'bought',
                ]);
            }

            return $this->wishlistRepository->updateWishlist($wishlist, [
                'status' => 'archived',
                'delay_days' => 0,
            ]);
        });
    }
}

