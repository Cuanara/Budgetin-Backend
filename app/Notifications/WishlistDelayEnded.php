<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class WishlistDelayEnded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Wishlist $wishlist) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable instanceof User && $notifiable->userDeviceTokens()->exists()) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * @return array{
     *     wishlist_id: string,
     *     item_name: string,
     *     price: float,
     *     title: string,
     *     message: string
     * }
     */
    public function toArray(object $notifiable): array
    {
        return [
            'wishlist_id' => $this->wishlist->id,
            'item_name' => $this->wishlist->item_name,
            'price' => (float) $this->wishlist->price,
            'title' => 'Masa tunda berakhir',
            'message' => "Sudah \"{$this->wishlist->delay_days}\" hari sejak kamu menyimpan barang ini.",
        ];
    }

    // payload yang dikirim lewat Reverb
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    // nilai 'type' yang diterima di frontend (default-nya nama class)
    public function broadcastType(): string
    {
        return 'wishlist_delay_ended';
    }

    // Isi kolom `type` (default-nya nama class lengkap)
    public function databaseType(object $notifiable): string
    {
        return 'wishlist_delay_ended';
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $data = $this->toArray($notifiable);

        return (new FcmMessage(notification: new FcmNotification(
            title: $data['title'],
            body: $data['message'],
        )))->data([
            'type' => 'wishlist_delay_ended',
            'wishlist_id' => (string) $this->wishlist->id, // data FCM wajib string
        ]);
    }
}
