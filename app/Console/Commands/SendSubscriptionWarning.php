<?php

namespace App\Console\Commands;

use App\Models\StoreSubscription;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendSubscriptionWarning extends Command
{
    protected $signature = 'subscription:send-warnings';

    protected $description = 'Kirim notifikasi FCM ke staff toko yang masa aktifnya akan berakhir dalam 7 hari.';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(7);

        // Cari langganan non-lifetime yang end_date-nya tepat 7 hari dari sekarang
        $subscriptions = StoreSubscription::with('store.users')
            ->where('package_type', '!=', 'lifetime')
            ->whereDate('end_date', $targetDate)
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('Tidak ada toko yang mendekati tanggal kedaluwarsa (H-7).');
            return self::SUCCESS;
        }

        $totalSent = 0;

        foreach ($subscriptions as $subscription) {
            $store = $subscription->store;

            if (!$store) {
                continue;
            }

            $title = 'Masa Aktif Toko Hampir Habis';
            $body  = "Masa aktif toko \"{$store->name}\" akan berakhir dalam 7 hari ({$subscription->end_date->format('d M Y')}). Silakan hubungi administrator untuk melakukan perpanjangan.";

            // Ambil semua user yang terhubung ke toko ini dan punya fcm_token
            $users = $store->users()->whereNotNull('fcm_token')->get();

            foreach ($users as $user) {
                $sent = FirebaseService::sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    [
                        'type'     => 'subscription_warning',
                        'store_id' => (string) $store->id,
                        'end_date' => $subscription->end_date->format('Y-m-d'),
                    ]
                );

                if ($sent) {
                    $totalSent++;
                }
            }

            $this->info("Notifikasi terkirim untuk toko: {$store->name} ({$users->count()} user)");
        }

        $this->info("Total notifikasi berhasil dikirim: {$totalSent}");

        return self::SUCCESS;
    }
}
