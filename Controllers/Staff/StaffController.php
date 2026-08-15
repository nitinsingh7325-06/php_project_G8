<?php

declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\BookingService;

class StaffController extends Controller
{
    public function dashboard(): void
    {
        $user = Session::user();
        $staffId = (int) $user['id'];
        $today = date('Y-m-d');

        // Today's appointments assigned to this staff member
        $todayAppointments = Database::fetchAll(
            "SELECT a.*, u.name AS customer_name, u.phone AS customer_phone
             FROM appointments a
             JOIN users u ON u.id = a.customer_id
             WHERE a.staff_id = ? AND a.appointment_date = ?
             ORDER BY a.start_time ASC",
            [$staffId, $today]
        );

        foreach ($todayAppointments as &$appt) {
            $appt['services'] = Database::fetchAll(
                "SELECT s.name, asv.price, asv.duration_minutes
                 FROM appointment_services asv
                 JOIN services s ON s.id = asv.service_id
                 WHERE asv.appointment_id = ?",
                [$appt['id']]
            );
        }
        unset($appt);

        // Upcoming appointments assigned to this staff member
        $upcomingAppointments = Database::fetchAll(
            "SELECT a.*, u.name AS customer_name, u.phone AS customer_phone
             FROM appointments a
             JOIN users u ON u.id = a.customer_id
             WHERE a.staff_id = ? AND a.appointment_date > ?
             ORDER BY a.appointment_date ASC, a.start_time ASC
             LIMIT 15",
            [$staffId, $today]
        );

        foreach ($upcomingAppointments as &$appt) {
            $appt['services'] = Database::fetchAll(
                "SELECT s.name, asv.price, asv.duration_minutes
                 FROM appointment_services asv
                 JOIN services s ON s.id = asv.service_id
                 WHERE asv.appointment_id = ?",
                [$appt['id']]
            );
        }
        unset($appt);

        // Staff attendance today
        $attendance = Database::fetch(
            "SELECT * FROM attendance WHERE staff_id = ? AND date = ?",
            [$staffId, $today]
        );

        // Staff schedule
        $schedules = Database::fetchAll(
            "SELECT * FROM staff_schedules WHERE staff_id = ? ORDER BY day_of_week",
            [$staffId]
        );

        // Staff recent salary records
        $salaries = Database::fetchAll(
            "SELECT * FROM salaries WHERE staff_id = ? ORDER BY year DESC, month DESC LIMIT 6",
            [$staffId]
        );

        $this->view('staff/dashboard', [
            'title' => 'Staff Dashboard',
            'user' => $user,
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'attendance' => $attendance,
            'schedules' => $schedules,
            'salaries' => $salaries,
        ], 'dashboard');
    }

    public function updateStatus(string $id): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 422);
        }

        $user = Session::user();
        $staffId = (int) $user['id'];
        $status = (string) $this->input('status');

        $appt = Database::fetch("SELECT * FROM appointments WHERE id = ?", [$id]);
        if (!$appt) {
            $this->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        if ($user['role'] === 'staff' && (int) $appt['staff_id'] !== $staffId) {
            $this->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        (new BookingService())->updateStatus((int) $id, $status);
        $this->json(['success' => true, 'message' => 'Appointment status updated to ' . $status]);
    }

    public function markAttendance(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 422);
        }

        $user = Session::user();
        $staffId = (int) $user['id'];
        $action = (string) $this->input('action'); // 'check_in' or 'check_out'
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');

        $existing = Database::fetch("SELECT * FROM attendance WHERE staff_id = ? AND date = ?", [$staffId, $today]);

        if ($action === 'check_in') {
            if ($existing && !empty($existing['check_in'])) {
                $this->json(['success' => false, 'message' => 'Already checked in today at ' . $existing['check_in']], 422);
            }

            Database::query(
                "INSERT INTO attendance (staff_id, date, check_in, status)
                 VALUES (?, ?, ?, 'Present')
                 ON DUPLICATE KEY UPDATE check_in = VALUES(check_in), status = 'Present'",
                [$staffId, $today, $nowTime]
            );
            $this->json(['success' => true, 'message' => 'Check-in recorded at ' . date('g:i A')]);
        } elseif ($action === 'check_out') {
            if (!$existing || empty($existing['check_in'])) {
                $this->json(['success' => false, 'message' => 'Please check in first before checking out'], 422);
            }

            Database::query(
                "UPDATE attendance SET check_out = ? WHERE staff_id = ? AND date = ?",
                [$nowTime, $staffId, $today]
            );
            $this->json(['success' => true, 'message' => 'Check-out recorded at ' . date('g:i A')]);
        } else {
            $this->json(['success' => false, 'message' => 'Invalid action'], 422);
        }
    }
}
