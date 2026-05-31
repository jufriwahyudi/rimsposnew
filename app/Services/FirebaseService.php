<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    /**
     * Send FCM push notification using FCM V1 HTTP API via kreait/laravel-firebase.
     */
    public static function sendNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::new()
                ->withToken($fcmToken)
                ->withNotification(Notification::create($title, $body));

            if (!empty($data)) {
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[(string)$key] = (string)$value;
                }
                $message = $message->withData($stringData);
            }

            $messaging->send($message);

            Log::info("FCM notification sent to token: {$fcmToken}");
            return true;
        } catch (\Throwable $e) {
            Log::error('FCM send exception: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple tokens using multicast.
     */
    public static function sendToMany(array $fcmTokens, string $title, string $body, array $data = []): int
    {
        $fcmTokens = array_filter(array_unique($fcmTokens));
        if (empty($fcmTokens)) {
            return 0;
        }

        try {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body));

            if (!empty($data)) {
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[(string)$key] = (string)$value;
                }
                $message = $message->withData($stringData);
            }

            $report = $messaging->sendMulticast($message, $fcmTokens);

            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            Log::info("FCM multicast notification sent. Successes: {$successCount}, Failures: {$failureCount}");

            return $successCount;
        } catch (\Throwable $e) {
            Log::error('FCM sendToMany exception: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return 0;
        }
    }
}

