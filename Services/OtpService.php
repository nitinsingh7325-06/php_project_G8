<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * OTP service with SMS (Twilio/Vonage) + email fallback.
 * Rate limiting: 3 attempts / 10 minutes via Redis or file cache.
 */
class OtpService
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function send(string $phone, string $purpose = 'login', ?string $email = null): array
    {
        $phone = format_phone($phone);
        $rateKey = 'otp_rate:' . $phone;

        $attempts = (int) $this->cache->get($rateKey, 0);
        $maxAttempts = (int) config('otp.max_attempts', 3);
        $window = (int) config('otp.rate_window', 10) * 60;

        if ($attempts >= $maxAttempts) {
            return ['success' => false, 'message' => 'Too many OTP requests. Please try again later.'];
        }

        $length = (int) config('otp.length', 6);
        $otp = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $expiry = (int) config('otp.expiry', 5);

        Database::insert(
            'INSERT INTO otp_verifications (phone, email, otp_hash, purpose, channel, expires_at, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $phone,
                $email,
                password_hash($otp, PASSWORD_BCRYPT),
                $purpose,
                $this->resolveChannel($email),
                date('Y-m-d H:i:s', time() + $expiry * 60),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );

        $this->cache->set($rateKey, $attempts + 1, $window);

        $channel = $this->deliver($phone, $otp, $email);
        $payload = [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'expires_in' => $expiry * 60,
            'channel' => $channel,
        ];

        if (env('APP_DEBUG', false) && env('SMS_PROVIDER', 'log') === 'log') {
            $payload['debug_otp'] = $otp;
        }

        return $payload;
    }

    public function verify(string $phone, string $otp, string $purpose = 'login'): array
    {
        $phone = format_phone($phone);
        $row = Database::fetch(
            'SELECT * FROM otp_verifications
             WHERE phone = ? AND purpose = ? AND is_used = 0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [$phone, $purpose]
        );

        if (!$row) {
            return ['success' => false, 'message' => 'OTP expired or not found.'];
        }

        if ((int) $row['attempts'] >= (int) $row['max_attempts']) {
            return ['success' => false, 'message' => 'Maximum verification attempts exceeded.'];
        }

        Database::query('UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);

        if (!password_verify($otp, $row['otp_hash'])) {
            return ['success' => false, 'message' => 'Invalid OTP.'];
        }

        Database::query(
            'UPDATE otp_verifications SET is_used = 1, verified_at = NOW() WHERE id = ?',
            [$row['id']]
        );

        $this->cache->forget('otp_rate:' . $phone);

        return ['success' => true, 'message' => 'OTP verified.', 'phone' => $phone];
    }

    public function resend(string $phone, string $purpose = 'login', ?string $email = null): array
    {
        return $this->send($phone, $purpose, $email);
    }

    private function resolveChannel(?string $email): string
    {
        $provider = env('SMS_PROVIDER', 'log');
        if (in_array($provider, ['twilio', 'vonage'], true)) {
            return 'sms';
        }
        if ($email && env('OTP_FALLBACK_EMAIL', true)) {
            return 'email';
        }
        return 'log';
    }

    private function deliver(string $phone, string $otp, ?string $email): string
    {
        $provider = env('SMS_PROVIDER', 'log');
        $message = "Your The Wave Men's Salon OTP is {$otp}. Valid for " . config('otp.expiry', 5) . " minutes. Do not share.";

        try {
            if ($provider === 'twilio' && env('TWILIO_SID') && class_exists(\Twilio\Rest\Client::class)) {
                $client = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
                $client->messages->create($phone, [
                    'from' => env('TWILIO_FROM'),
                    'body' => $message,
                ]);
                return 'sms';
            }

            if ($provider === 'vonage' && env('VONAGE_KEY')) {
                $this->sendVonage($phone, $message);
                return 'sms';
            }
        } catch (\Throwable $e) {
            log_message('error', 'SMS delivery failed', ['error' => $e->getMessage()]);
        }

        if ($email && env('OTP_FALLBACK_EMAIL', true)) {
            $this->sendEmail($email, $otp);
            return 'email';
        }

        log_message('info', 'OTP (log channel)', ['phone' => $phone, 'otp' => $otp]);
        return 'log';
    }

    private function sendVonage(string $phone, string $message): void
    {
        $url = 'https://rest.nexmo.com/sms/json';
        $payload = http_build_query([
            'api_key' => env('VONAGE_KEY'),
            'api_secret' => env('VONAGE_SECRET'),
            'to' => ltrim($phone, '+'),
            'from' => env('VONAGE_FROM', 'TheWave'),
            'text' => $message,
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendEmail(string $email, string $otp): void
    {
        $subject = "Your OTP - The Wave Men's Salon";
        $body = "Your verification code is: {$otp}\n\nValid for " . config('otp.expiry', 5) . " minutes.";
        $headers = 'From: ' . env('MAIL_FROM', 'noreply@thewavemenssalon.com');
        @mail($email, $subject, $body, $headers);
        log_message('info', 'OTP email sent', ['email' => $email]);
    }
}
