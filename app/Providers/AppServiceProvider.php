<?php

namespace App\Providers;

use App\Models\UserDeviceToken;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use NotificationChannels\Fcm\FcmChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureNotificationListeners();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Catat notifikasi yang gagal & hapus token FCM yang sudah tidak valid.
     */
    protected function configureNotificationListeners(): void
    {
        Event::listen(NotificationFailed::class, function (NotificationFailed $event) {
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
                UserDeviceToken::where('token', $report->target()->value())->delete();
            }
        });
    }
}
