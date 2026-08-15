<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function markPaid(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $paymentId = (int) $this->input('payment_id');
        $method = (string) $this->input('method', 'UPI');
        (new PaymentService())->markPaid($paymentId, $method, $this->input('gateway_payment_id'));
        $this->json(['success' => true, 'message' => 'Payment recorded.']);
    }

    public function webhook(): void
    {
        $payload = file_get_contents('php://input') ?: '{}';
        log_message('info', 'Payment webhook', ['payload' => $payload]);
        $data = json_decode($payload, true) ?: [];

        if (($data['type'] ?? '') === 'payment_intent.succeeded') {
            $pi = $data['data']['object']['id'] ?? null;
            if ($pi) {
                $payment = Database::fetch('SELECT * FROM payments WHERE gateway_payment_id = ?', [$pi]);
                if ($payment) {
                    (new PaymentService())->markPaid((int) $payment['id'], 'Stripe', $pi);
                }
            }
        }

        if (($data['event'] ?? '') === 'payment.captured') {
            $orderId = $data['payload']['payment']['entity']['order_id'] ?? null;
            $payId = $data['payload']['payment']['entity']['id'] ?? null;
            if ($orderId) {
                $payment = Database::fetch('SELECT * FROM payments WHERE gateway_order_id = ?', [$orderId]);
                if ($payment) {
                    (new PaymentService())->markPaid((int) $payment['id'], 'Razorpay', $payId);
                }
            }
        }

        $this->json(['received' => true]);
    }
}
