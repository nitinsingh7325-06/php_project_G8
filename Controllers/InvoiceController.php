<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function generate(): void
    {
        if (!Session::isLoggedIn()) {
            $this->json(['success' => false, 'message' => 'Please login'], 401);
        }

        $appointmentId = (int) $this->input('appointment_id');
        if (!$appointmentId) {
            $bookingId = (string) $this->input('booking_id');
            if ($bookingId) {
                $appt = Database::fetch('SELECT id, customer_id FROM appointments WHERE booking_id = ?', [$bookingId]);
                if ($appt) {
                    $appointmentId = (int) $appt['id'];
                }
            }
        }

        if (!$appointmentId) {
            $this->json(['success' => false, 'message' => 'Invalid appointment ID'], 422);
        }

        $appt = Database::fetch('SELECT * FROM appointments WHERE id = ?', [$appointmentId]);
        if (!$appt) {
            $this->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        $user = Session::user();
        if ($user['role'] === 'customer' && (int) $appt['customer_id'] !== (int) $user['id']) {
            $this->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $invoice = (new InvoiceService())->generateFromAppointment($appointmentId);
        if (!$invoice) {
            $this->json(['success' => false, 'message' => 'Failed to generate invoice'], 500);
        }

        $this->json([
            'success' => true,
            'message' => 'Invoice generated successfully.',
            'invoice_id' => $invoice['id'],
            'invoice_number' => $invoice['invoice_number'],
            'url' => url('invoices/' . $invoice['id']),
        ]);
    }

    public function show(string $id): void
    {
        if (!Session::isLoggedIn()) {
            redirect(url('login'));
            return;
        }

        $invoice = Database::fetch(
            'SELECT i.*, u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email, a.booking_id, a.appointment_date, a.start_time, s.name AS staff_name
             FROM invoices i
             JOIN users u ON u.id = i.customer_id
             LEFT JOIN appointments a ON a.id = i.appointment_id
             LEFT JOIN users s ON s.id = a.staff_id
             WHERE i.id = ? OR i.invoice_number = ?',
            [$id, $id]
        );

        if (!$invoice) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Invoice Not Found']);
            return;
        }

        $user = Session::user();
        if ($user['role'] === 'customer' && (int) $invoice['customer_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $items = Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', [$invoice['id']]);

        $this->view('invoice/show', [
            'title' => 'Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $items,
        ]);
    }
}
