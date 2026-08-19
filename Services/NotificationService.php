<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class NotificationService
{
    public function notify(int $userId, string $title, string $body, string $type = 'general', array $data = []): void
    {
        Database::insert(
            'INSERT INTO notifications (user_id, title, body, type, data, sent_via) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $title, $body, $type, $data ? json_encode($data) : null, 'app']
        );

        if (env('FCM_ENABLED', false) && env('FCM_SERVER_KEY')) {
            $user = Database::fetch('SELECT fcm_token FROM users WHERE id = ?', [$userId]);
            if (!empty($user['fcm_token'])) {
                $this->sendFcm($user['fcm_token'], $title, $body, $data);
            }
        }
    }

    private function sendFcm(string $token, string $title, string $body, array $data): void
    {
        $payload = json_encode([
            'to' => $token,
            'notification' => ['title' => $title, 'body' => $body],
            'data' => $data,
        ]);
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . env('FCM_SERVER_KEY'),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
