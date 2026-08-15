<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;

class BookingController extends Controller
{
    public function index(): void
    {
        $this->view('home/book', [
            'title' => 'Book Appointment',
            'services' => Service::active(),
            'categories' => Service::categories(),
            'staff' => User::staffList(),
            'user' => Session::user(),
        ]);
    }

    public function availability(): void
    {
        $date = (string) $this->input('date');
        $staffId = $this->input('staff_id') ? (int) $this->input('staff_id') : null;
        $duration = (int) $this->input('duration', 30);
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['success' => false, 'message' => 'Invalid date'], 422);
        }
        $slots = (new BookingService())->getAvailableSlots($date, $staffId, max(15, $duration));
        $this->json(['success' => true, 'slots' => $slots]);
    }

    public function store(): void
    {
        if (!Session::isLoggedIn()) {
            $this->json(['success' => false, 'message' => 'Please login to book', 'redirect' => url('login')], 401);
        }
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }

        $services = $this->input('services');
        if (is_string($services)) {
            $services = json_decode($services, true) ?: [];
        }
        $user = Session::user();
        $result = (new BookingService())->create([
            'customer_id' => $user['id'],
            'staff_id' => $this->input('staff_id'),
            'appointment_date' => $this->input('appointment_date'),
            'start_time' => $this->input('start_time'),
            'services' => array_map('intval', (array) $services),
            'notes' => $this->input('notes'),
            'offer_code' => $this->input('offer_code'),
        ]);

        if ($result['success']) {
            $pay = (new PaymentService())->create([
                'appointment_id' => $result['appointment_id'],
                'customer_id' => $user['id'],
                'amount' => $result['final_amount'],
                'method' => $this->input('payment_method', 'Cash'),
            ]);
            $result['payment_id'] = $pay['payment_id'];
            $result['payment'] = (new PaymentService())->initiateGateway((int) $pay['payment_id']);
        }

        $this->json($result, $result['success'] ? 200 : 422);
    }

    public function history(): void
    {
        $user = Session::user();
        $this->view('customer/appointments', [
            'title' => 'My Appointments',
            'appointments' => Appointment::forCustomer((int) $user['id']),
        ], 'dashboard');
    }

    public function show(string $bookingId): void
    {
        $appt = Appointment::byBookingId($bookingId);
        if (!$appt) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Not Found']);
            return;
        }
        $user = Session::user();
        if ($user && $user['role'] === 'customer' && (int) $appt['customer_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }
        $this->view('customer/appointment_detail', [
            'title' => 'Booking ' . $bookingId,
            'appointment' => $appt,
        ], 'dashboard');
    }

    public function cancel(string $id): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $user = Session::user();
        $appt = Appointment::find((int) $id);
        if (!$appt || ((int) $appt['customer_id'] !== (int) $user['id'] && $user['role'] === 'customer')) {
            $this->json(['success' => false, 'message' => 'Not found'], 404);
        }
        (new BookingService())->updateStatus((int) $id, 'Cancelled', (string) $this->input('reason', 'Cancelled by customer'));
        $this->json(['success' => true, 'message' => 'Appointment cancelled.']);
    }

    public function dashboard(): void
    {
        $user = Session::user();
        $fresh = User::find((int) $user['id']);
        $appts = Appointment::forCustomer((int) $user['id']);
        $invoices = Database::fetchAll(
            'SELECT * FROM invoices WHERE customer_id = ? ORDER BY id DESC LIMIT 10',
            [$user['id']]
        );
        $this->view('customer/dashboard', [
            'title' => 'Dashboard',
            'user' => $fresh,
            'appointments' => array_slice($appts, 0, 5),
            'invoices' => $invoices,
            'tier' => loyalty_tier((int) ($fresh['loyalty_points'] ?? 0)),
        ], 'dashboard');
    }
}
