<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('budget:check-rollover')->monthlyOn(1, '00:01');
Schedule::command('model:prune')->daily();
Schedule::command('wishlist:notify-delay-ended')
    ->hourly()
    ->between('8:00', '20:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onOneServer();
