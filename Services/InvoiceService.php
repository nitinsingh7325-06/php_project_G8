<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class InvoiceService
{
    public function generateFromAppointment(int $appointmentId): ?array
    {
        $appt = Database::fetch(
            'SELECT a.*, u.name AS customer_name, u.phone, u.email
             FROM appointments a JOIN users u ON u.id = a.customer_id WHERE a.id = ?',
            [$appointmentId]
        );
        if (!$appt) {
            return null;
        }

        $existing = Database::fetch('SELECT * FROM invoices WHERE appointment_id = ?', [$appointmentId]);
        if ($existing) {
            return $existing;
        }

        $items = Database::fetchAll(
            'SELECT s.name, asv.price, asv.duration_minutes
             FROM appointment_services asv JOIN services s ON s.id = asv.service_id
             WHERE asv.appointment_id = ?',
            [$appointmentId]
        );

        $taxPercent = (float) (Database::fetch("SELECT setting_value FROM settings WHERE setting_key='tax_percent'")['setting_value'] ?? 18);
        $subtotal = (float) $appt['total_amount'];
        $discount = (float) $appt['discount_amount'];
        $taxable = max(0, $subtotal - $discount);
        $tax = round($taxable * $taxPercent / 100, 2);
        $total = $taxable + $tax;

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $invoiceId = Database::insert(
            'INSERT INTO invoices (invoice_number, appointment_id, customer_id, subtotal, tax_amount, discount_amount, total, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$invoiceNumber, $appointmentId, $appt['customer_id'], $subtotal, $tax, $discount, $total, 'Issued']
        );

        foreach ($items as $item) {
            Database::insert(
                'INSERT INTO invoice_items (invoice_id, description, qty, unit_price, total) VALUES (?, ?, 1, ?, ?)',
                [$invoiceId, $item['name'], $item['price'], $item['price']]
            );
        }

        $pdfPath = $this->renderPdf($invoiceId);
        Database::query('UPDATE invoices SET pdf_path = ? WHERE id = ?', [$pdfPath, $invoiceId]);

        return Database::fetch('SELECT * FROM invoices WHERE id = ?', [$invoiceId]);
    }

    public function renderPdf(int $invoiceId): string
    {
        $invoice = Database::fetch(
            'SELECT i.*, u.name AS customer_name, u.phone, u.email, a.booking_id
             FROM invoices i
             JOIN users u ON u.id = i.customer_id
             LEFT JOIN appointments a ON a.id = i.appointment_id
             WHERE i.id = ?',
            [$invoiceId]
        );
        $items = Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);

        $html = $this->buildHtml($invoice, $items);
        $dir = storage_path('invoices');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $invoice['invoice_number'] . '.html';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $html);

        // DomPDF when available
        if (class_exists(\Dompdf\Dompdf::class)) {
            try {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4');
                $dompdf->render();
                $pdfFile = $dir . '/' . $invoice['invoice_number'] . '.pdf';
                file_put_contents($pdfFile, $dompdf->output());
                $publicDir = dirname(__DIR__, 2) . '/public/storage/invoices';
                if (!is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                @copy($pdfFile, $publicDir . '/' . $invoice['invoice_number'] . '.pdf');
                return 'storage/invoices/' . $invoice['invoice_number'] . '.pdf';
            } catch (\Throwable $e) {
                log_message('warning', 'DomPDF failed, using HTML', ['error' => $e->getMessage()]);
            }
        }

        $publicDir = dirname(__DIR__, 2) . '/public/storage/invoices';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        @copy($path, $publicDir . '/' . $filename);
        return 'storage/invoices/' . $filename;
    }

    private function buildHtml(array $invoice, array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td>' . e($item['description']) . '</td><td>' . (int) $item['qty'] . '</td><td>' . money($item['unit_price']) . '</td><td>' . money($item['total']) . '</td></tr>';
        }
        $salon = e((string) config('salon.name'));
        $invNo = e($invoice['invoice_number']);
        $booking = e((string) ($invoice['booking_id'] ?? ''));
        $customer = e($invoice['customer_name']);
        $phone = e((string) $invoice['phone']);
        $issued = e($invoice['issued_at']);
        $sub = money($invoice['subtotal']);
        $disc = money($invoice['discount_amount']);
        $tax = money($invoice['tax_amount']);
        $tot = money($invoice['total']);
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#121212;padding:40px}
h1{color:#D4AF37;margin:0}
.meta{margin:20px 0;color:#555}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ddd;padding:10px;text-align:left}
th{background:#121212;color:#D4AF37}
.total{text-align:right;margin-top:20px;font-size:18px}
</style></head><body>
<h1>{$salon}</h1>
<p class="meta">Invoice: {$invNo}<br>
Booking: {$booking}<br>
Customer: {$customer} ({$phone})<br>
Date: {$issued}</p>
<table><thead><tr><th>Service</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
<tbody>{$rows}</tbody></table>
<div class="total">
Subtotal: {$sub}<br>
Discount: {$disc}<br>
Tax: {$tax}<br>
<strong>Total: {$tot}</strong>
</div>
</body></html>
HTML;
    }
}
