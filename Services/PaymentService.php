<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class PaymentService
{
    public function create(array $data): array
    {
        $id = Database::insert(
            'INSERT INTO payments (appointment_id, customer_id, amount, method, status, gateway, gateway_order_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['appointment_id'] ?? null,
                $data['customer_id'],
                $data['amount'],
                $data['method'],
                'Pending',
                $data['gateway'] ?? env('PAYMENT_GATEWAY', 'stripe'),
                $data['order_id'] ?? null,
            ]
        );
        return ['success' => true, 'payment_id' => $id];
    }

    public function initiateGateway(int $paymentId): array
    {
        $payment = Database::fetch('SELECT * FROM payments WHERE id = ?', [$paymentId]);
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        $gateway = env('PAYMENT_GATEWAY', 'stripe');

        if ($gateway === 'razorpay' && env('RAZORPAY_KEY_ID')) {
            return $this->razorpayOrder($payment);
        }

        if ($gateway === 'stripe' && env('STRIPE_SECRET_KEY') && class_exists(\Stripe\Stripe::class)) {
            return $this->stripeIntent($payment);
        }

        // Offline / demo mode
        return [
            'success' => true,
            'mode' => 'offline',
            'message' => 'Complete payment via Cash at salon or mark paid in admin.',
            'payment_id' => $paymentId,
            'methods' => ['Cash'],
        ];
    }

    private function stripeIntent(array $payment): array
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $intent = \Stripe\PaymentIntent::create([
            'amount' => (int) round((float) $payment['amount'] * 100),
            'currency' => 'inr',
            'metadata' => ['payment_id' => $payment['id']],
        ]);
        Database::query(
            'UPDATE payments SET gateway_payment_id = ?, gateway = ? WHERE id = ?',
            [$intent->id, 'stripe', $payment['id']]
        );
        return [
            'success' => true,
            'mode' => 'stripe',
            'client_secret' => $intent->client_secret,
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'payment_id' => $payment['id'],
        ];
    }

    private function razorpayOrder(array $payment): array
    {
        $key = env('RAZORPAY_KEY_ID');
        $secret = env('RAZORPAY_KEY_SECRET');
        $payload = json_encode([
            'amount' => (int) round((float) $payment['amount'] * 100),
            'currency' => 'INR',
            'receipt' => 'pay_' . $payment['id'],
        ]);
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => "{$key}:{$secret}",
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $order = json_decode((string) $response, true);
        if (!empty($order['id'])) {
            Database::query(
                'UPDATE payments SET gateway_order_id = ?, gateway = ? WHERE id = ?',
                [$order['id'], 'razorpay', $payment['id']]
            );
            return [
                'success' => true,
                'mode' => 'razorpay',
                'order_id' => $order['id'],
                'key' => $key,
                'amount' => $order['amount'],
                'payment_id' => $payment['id'],
            ];
        }
        return ['success' => false, 'message' => 'Unable to create Razorpay order'];
    }

    public function markPaid(int $paymentId, string $method = 'Cash', ?string $gatewayId = null): bool
    {
        Database::query(
            'UPDATE payments SET status = ?, method = ?, gateway_payment_id = COALESCE(?, gateway_payment_id), paid_at = NOW() WHERE id = ?',
            ['Paid', $method, $gatewayId, $paymentId]
        );
        $payment = Database::fetch('SELECT * FROM payments WHERE id = ?', [$paymentId]);
        if ($payment && $payment['appointment_id']) {
            Database::query(
                "UPDATE appointments SET status = 'Confirmed', confirmed_at = NOW() WHERE id = ? AND status = 'Pending'",
                [$payment['appointment_id']]
            );
        }
        return true;
    }
}
