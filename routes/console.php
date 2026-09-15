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

// Generate digital daily newspaper for stores at 23:59 WIB daily
Schedule::command('newspaper:generate')->dailyAt('23:59')->withoutOverlapping()->runInBackground();

// Reset kuota harian menu FnB pada 00:01 setiap hari
Artisan::command('pos:reset-daily-quota', function () {
    $today = now()->toDateString();
    $count = \App\Models\ProductVariant::where(function ($q) use ($today) {
        $q->whereNotNull('daily_quota')->whereDate('quota_date', '<', $today);
    })->orWhere(function ($q) use ($today) {
        $q->where('is_available', false)->whereNotNull('quota_date')->whereDate('quota_date', '<', $today);
    })->update([
        'daily_quota'  => null,
        'quota_date'   => null,
        'is_available' => true,
    ]);
    $this->info("Berhasil mereset kuota harian untuk {$count} varian menu.");
})->purpose('Reset kuota harian dan buka kembali menu yang telah kedaluwarsa')->dailyAt('00:01');

