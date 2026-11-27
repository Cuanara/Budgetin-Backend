<?php

namespace App\Listeners;

use App\Repositories\UserDeviceTokenRepository;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;

class HandleFailedNotification
{
    public function __construct(
        private readonly UserDeviceTokenRepository $deviceTokens,
    ) {}

    /**
     * Catat notifikasi yang gagal & hapus token FCM yang sudah tidak valid.
     */
    public function handle(NotificationFailed $event): void
    {
        Log::warning('Notifikasi gagal dikirim', [
            'channel' => $event->channel,
            'notification' => $event->notification::class,
            'user_id' => $event->notifiable->id ?? null,
        ]);

        if ($event->channel !== FcmChannel::class) {
            return;
        }

        $report = $event->data['report'] ?? null;

        if ($report) {
            $this->deviceTokens->deleteByToken($report->target()->value());
        }
    }
}
