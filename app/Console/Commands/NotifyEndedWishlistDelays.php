<?php

namespace App\Console\Commands;

use App\Services\WishlistService;
use Illuminate\Console\Command;

class NotifyEndedWishlistDelays extends Command
{
    protected $signature = 'wishlist:notify-delay-ended';

    protected $description = 'Ubah wishlist yang masa tundanya habis ke pending_decision dan kirim notifikasi';

    public function handle(WishlistService $service): int
    {
        $sent = $service->notifyEndedDelays();

        $this->info("Notifikasi terkirim: {$sent}");

        return self::SUCCESS;
    }
}
