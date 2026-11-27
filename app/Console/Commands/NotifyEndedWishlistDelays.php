<?php

namespace App\Console\Commands;

use App\Models\Wishlist;
use App\Notifications\WishlistDelayEnded;
use Illuminate\Console\Command;

class NotifyEndedWishlistDelays extends Command
{
    protected $signature = 'wishlist:notify-delay-ended';

    protected $description = 'Ubah wishlist yang masa tundanya habis ke pending_decision dan kirim notifikasi';

    public function handle(): int
    {
        $today = now('Asia/Jakarta')->toDateString();
        $sent = 0;

        Wishlist::query()
            ->where('status', 'delayed')
            ->whereNotNull('delay_until')
            ->where('delay_until', '<=', $today)
            ->with('user')
            ->chunkById(200, function ($wishlists) use (&$sent) {
                foreach ($wishlists as $wishlist) {
                    // Klaim atomik: hanya satu proses yang berhasil mengubah status
                    $claimed = Wishlist::whereKey($wishlist->id)
                        ->where('status', 'delayed')
                        ->update(['status' => 'pending_decision']);

                    if (! $claimed || ! $wishlist->user) {
                        continue;
                    }

                    $wishlist->status = 'pending_decision';
                    $wishlist->user->notify(new WishlistDelayEnded($wishlist));
                    $sent++;
                }
            });

        $this->info("Notifikasi terkirim: {$sent}");

        return self::SUCCESS;
    }
}
