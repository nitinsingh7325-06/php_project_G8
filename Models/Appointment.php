<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Appointment extends Model
{
    protected static string $table = 'appointments';

    public static function forCustomer(int $customerId): array
    {
        return Database::fetchAll(
            "SELECT a.*, u.name AS staff_name FROM appointments a
             LEFT JOIN users u ON u.id = a.staff_id
             WHERE a.customer_id = ? ORDER BY a.appointment_date DESC, a.start_time DESC",
            [$customerId]
        );
    }

    public static function withDetails(int $id): ?array
    {
        $appt = Database::fetch(
            "SELECT a.*, c.name AS customer_name, c.phone AS customer_phone, s.name AS staff_name
             FROM appointments a
             JOIN users c ON c.id = a.customer_id
             LEFT JOIN users s ON s.id = a.staff_id
             WHERE a.id = ?",
            [$id]
        );
        if (!$appt) {
            return null;
        }
        $appt['services'] = Database::fetchAll(
            "SELECT sv.*, asv.price AS booked_price FROM appointment_services asv
             JOIN services sv ON sv.id = asv.service_id WHERE asv.appointment_id = ?",
            [$id]
        );
        return $appt;
    }

    public static function byBookingId(string $bookingId): ?array
    {
        $row = self::firstWhere('booking_id', $bookingId);
        return $row ? self::withDetails((int) $row['id']) : null;
    }

    public static function adminList(int $page = 1, ?string $status = null): array
    {
        $where = '1=1';
        $params = [];
        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        return self::paginate($page, 20, $where, $params, 'appointment_date DESC, start_time DESC');
    }
}
