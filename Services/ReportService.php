<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class ReportService
{
    public function dashboardStats(): array
    {
        return [
            'customers' => (int) (Database::fetch("SELECT COUNT(*) c FROM users WHERE role='customer'")['c'] ?? 0),
            'staff' => (int) (Database::fetch("SELECT COUNT(*) c FROM users WHERE role='staff'")['c'] ?? 0),
            'appointments_today' => (int) (Database::fetch("SELECT COUNT(*) c FROM appointments WHERE appointment_date = CURDATE()")['c'] ?? 0),
            'revenue_month' => (float) (Database::fetch("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE status='Paid' AND MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())")['c'] ?? 0),
            'pending' => (int) (Database::fetch("SELECT COUNT(*) c FROM appointments WHERE status='Pending'")['c'] ?? 0),
            'completed_month' => (int) (Database::fetch("SELECT COUNT(*) c FROM appointments WHERE status='Completed' AND MONTH(completed_at)=MONTH(CURDATE())")['c'] ?? 0),
            'avg_rating' => (float) (Database::fetch("SELECT COALESCE(AVG(rating),0) c FROM reviews WHERE is_approved=1")['c'] ?? 0),
            'low_stock' => (int) (Database::fetch("SELECT COUNT(*) c FROM inventory WHERE quantity <= reorder_level AND is_active=1")['c'] ?? 0),
        ];
    }

    public function chartData(string $range = 'monthly'): array
    {
        return match ($range) {
            'daily' => $this->daily(),
            'weekly' => $this->weekly(),
            'yearly' => $this->yearly(),
            default => $this->monthly(),
        };
    }

    private function daily(): array
    {
        $rows = Database::fetchAll(
            "SELECT DATE_FORMAT(paid_at,'%H:00') label, SUM(amount) total
             FROM payments WHERE status='Paid' AND DATE(paid_at)=CURDATE()
             GROUP BY label ORDER BY label"
        );
        return $this->format($rows);
    }

    private function weekly(): array
    {
        $rows = Database::fetchAll(
            "SELECT DATE(paid_at) label, SUM(amount) total
             FROM payments WHERE status='Paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY label ORDER BY label"
        );
        return $this->format($rows);
    }

    private function monthly(): array
    {
        $rows = Database::fetchAll(
            "SELECT DATE(paid_at) label, SUM(amount) total
             FROM payments WHERE status='Paid' AND MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())
             GROUP BY label ORDER BY label"
        );
        return $this->format($rows);
    }

    private function yearly(): array
    {
        $rows = Database::fetchAll(
            "SELECT DATE_FORMAT(paid_at,'%Y-%m') label, SUM(amount) total
             FROM payments WHERE status='Paid' AND YEAR(paid_at)=YEAR(CURDATE())
             GROUP BY label ORDER BY label"
        );
        return $this->format($rows);
    }

    private function format(array $rows): array
    {
        return [
            'labels' => array_column($rows, 'label'),
            'data' => array_map('floatval', array_column($rows, 'total')),
        ];
    }

    public function exportCsv(string $type): string
    {
        $dir = storage_path('cache');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir . '/export_' . $type . '_' . date('Ymd_His') . '.csv';
        $fp = fopen($file, 'w');

        if ($type === 'appointments') {
            fputcsv($fp, ['Booking ID', 'Customer', 'Date', 'Time', 'Status', 'Amount']);
            $rows = Database::fetchAll(
                "SELECT a.booking_id, u.name, a.appointment_date, a.start_time, a.status, a.final_amount
                 FROM appointments a JOIN users u ON u.id=a.customer_id ORDER BY a.id DESC LIMIT 1000"
            );
            foreach ($rows as $r) {
                fputcsv($fp, $r);
            }
        } elseif ($type === 'payments') {
            fputcsv($fp, ['ID', 'Customer', 'Amount', 'Method', 'Status', 'Paid At']);
            $rows = Database::fetchAll(
                "SELECT p.id, u.name, p.amount, p.method, p.status, p.paid_at
                 FROM payments p JOIN users u ON u.id=p.customer_id ORDER BY p.id DESC LIMIT 1000"
            );
            foreach ($rows as $r) {
                fputcsv($fp, $r);
            }
        }

        fclose($fp);
        return $file;
    }
}
