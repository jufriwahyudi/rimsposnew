<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule::command('broadcast:wa --limit=1')->everyThreeMinutes()->withoutOverlapping();

// Kirim pengingat FCM H-7 masa aktif langganan toko (setiap hari jam 08:00)
Schedule::command('subscription:send-warnings')->dailyAt('08:00')->withoutOverlapping();
