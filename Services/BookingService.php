<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class BookingService
{
    public function getAvailableSlots(string $date, ?int $staffId, int $durationMinutes = 30): array
    {
        $open = env('SALON_OPEN', '09:00');
        $close = env('SALON_CLOSE', '21:00');
        $slotMins = (int) (Database::fetch("SELECT setting_value FROM settings WHERE setting_key='booking_slot_minutes'")['setting_value'] ?? 30);

        $dayOfWeek = (int) date('w', strtotime($date));
        if ($staffId) {
            $schedule = Database::fetch(
                'SELECT * FROM staff_schedules WHERE staff_id = ? AND day_of_week = ?',
                [$staffId, $dayOfWeek]
            );
            if ($schedule) {
                if ((int) $schedule['is_off'] === 1) {
                    return [];
                }
                $open = substr($schedule['start_time'], 0, 5);
                $close = substr($schedule['end_time'], 0, 5);
            }
        }

        $booked = Database::fetchAll(
            "SELECT start_time, end_time, staff_id FROM appointments
             WHERE appointment_date = ? AND status IN ('Pending','Confirmed')
             AND (? IS NULL OR staff_id = ? OR staff_id IS NULL)",
            [$date, $staffId, $staffId]
        );

        $slots = [];
        $start = strtotime($date . ' ' . $open);
        $end = strtotime($date . ' ' . $close);
        $now = time();

        for ($t = $start; $t + ($durationMinutes * 60) <= $end; $t += $slotMins * 60) {
            if ($date === date('Y-m-d') && $t < $now + 1800) {
                continue;
            }
            $slotStart = date('H:i:s', $t);
            $slotEnd = date('H:i:s', $t + $durationMinutes * 60);
            if (!$this->hasConflict($booked, $slotStart, $slotEnd, $staffId)) {
                $slots[] = [
                    'start' => date('H:i', $t),
                    'end' => date('H:i', $t + $durationMinutes * 60),
                    'label' => date('g:i A', $t),
                ];
            }
        }
        return $slots;
    }

    private function hasConflict(array $booked, string $start, string $end, ?int $staffId): bool
    {
        foreach ($booked as $b) {
            if ($staffId && $b['staff_id'] && (int) $b['staff_id'] !== $staffId) {
                continue;
            }
            if ($start < $b['end_time'] && $end > $b['start_time']) {
                return true;
            }
        }
        return false;
    }

    public function create(array $data): array
    {
        $services = $data['services'] ?? [];
        if (empty($services)) {
            return ['success' => false, 'message' => 'Select at least one service.'];
        }

        $serviceRows = Database::fetchAll(
            'SELECT * FROM services WHERE id IN (' . implode(',', array_fill(0, count($services), '?')) . ') AND is_active = 1',
            $services
        );
        if (count($serviceRows) !== count($services)) {
            return ['success' => false, 'message' => 'Invalid service selection.'];
        }

        $duration = array_sum(array_column($serviceRows, 'duration_minutes'));
        $total = 0;
        foreach ($serviceRows as $s) {
            $total += (float) ($s['discount_price'] ?? $s['price']);
        }

        $discount = 0;
        if (!empty($data['offer_code'])) {
            $offer = Database::fetch(
                'SELECT * FROM offers WHERE code = ? AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())',
                [strtoupper($data['offer_code'])]
            );
            if ($offer && $total >= (float) $offer['min_amount']) {
                $discount = $offer['discount_type'] === 'percent'
                    ? round($total * ((float) $offer['discount_value'] / 100), 2)
                    : (float) $offer['discount_value'];
            }
        }

        $final = max(0, $total - $discount);
        $startTime = $data['start_time'];
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }
        $endTime = date('H:i:s', strtotime($data['appointment_date'] . ' ' . $startTime) + $duration * 60);

        $staffId = !empty($data['staff_id']) ? (int) $data['staff_id'] : null;
        $slots = $this->getAvailableSlots($data['appointment_date'], $staffId, $duration);
        $startLabel = substr($startTime, 0, 5);
        $available = array_filter($slots, fn($s) => $s['start'] === $startLabel);
        if (!$available) {
            return ['success' => false, 'message' => 'Selected slot is no longer available.'];
        }

        Database::beginTransaction();
        try {
            $bookingId = booking_id();
            $apptId = Database::insert(
                'INSERT INTO appointments (booking_id, customer_id, staff_id, appointment_date, start_time, end_time, status, notes, total_amount, discount_amount, final_amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $bookingId,
                    $data['customer_id'],
                    $staffId,
                    $data['appointment_date'],
                    $startTime,
                    $endTime,
                    'Pending',
                    $data['notes'] ?? null,
                    $total,
                    $discount,
                    $final,
                ]
            );

            foreach ($serviceRows as $s) {
                Database::insert(
                    'INSERT INTO appointment_services (appointment_id, service_id, price, duration_minutes) VALUES (?, ?, ?, ?)',
                    [$apptId, $s['id'], $s['discount_price'] ?? $s['price'], $s['duration_minutes']]
                );
            }

            $qr = new QrCodeService();
            $qrPath = $qr->generate($bookingId);
            Database::query('UPDATE appointments SET qr_code = ? WHERE id = ?', [$qrPath, $apptId]);

            if (!empty($data['offer_code']) && isset($offer)) {
                Database::query('UPDATE offers SET used_count = used_count + 1 WHERE id = ?', [$offer['id']]);
            }

            // Google Calendar sync (optional)
            (new GoogleCalendarService())->createEvent([
                'booking_id' => $bookingId,
                'date' => $data['appointment_date'],
                'start' => $startTime,
                'end' => $endTime,
                'summary' => "Appointment {$bookingId}",
            ], $apptId);

            Database::commit();

            (new NotificationService())->notify(
                (int) $data['customer_id'],
                'Booking Confirmed',
                "Your appointment {$bookingId} is scheduled for {$data['appointment_date']} at {$startLabel}.",
                'booking'
            );

            return [
                'success' => true,
                'message' => 'Appointment booked successfully.',
                'booking_id' => $bookingId,
                'appointment_id' => $apptId,
                'final_amount' => $final,
            ];
        } catch (\Throwable $e) {
            Database::rollBack();
            log_message('error', 'Booking failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Booking failed. Please try again.'];
        }
    }

    public function updateStatus(int $id, string $status, ?string $reason = null): bool
    {
        $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No-Show'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $extra = '';
        $params = [$status];
        if ($status === 'Cancelled') {
            $extra = ', cancellation_reason = ?, cancelled_at = NOW()';
            $params[] = $reason;
        } elseif ($status === 'Confirmed') {
            $extra = ', confirmed_at = NOW()';
        } elseif ($status === 'Completed') {
            $extra = ', completed_at = NOW()';
        }
        $params[] = $id;
        Database::query("UPDATE appointments SET status = ? {$extra} WHERE id = ?", $params);

        if ($status === 'Completed') {
            $appt = Database::fetch('SELECT * FROM appointments WHERE id = ?', [$id]);
            if ($appt) {
                (new LoyaltyService())->earn((int) $appt['customer_id'], (float) $appt['final_amount'], 'appointment', $id);
                (new InvoiceService())->generateFromAppointment($id);
            }
        }
        return true;
    }
}
