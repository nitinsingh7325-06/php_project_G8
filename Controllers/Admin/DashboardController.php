<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $reports = new ReportService();
        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $reports->dashboardStats(),
            'chart' => $reports->chartData('monthly'),
            'recent' => Database::fetchAll(
                "SELECT a.*, u.name AS customer_name FROM appointments a
                 JOIN users u ON u.id=a.customer_id ORDER BY a.id DESC LIMIT 8"
            ),
        ], 'admin');
    }

    public function chartData(): void
    {
        $range = (string) $this->input('range', 'monthly');
        $this->json(['success' => true, 'chart' => (new ReportService())->chartData($range)]);
    }

    public function export(): void
    {
        $type = (string) $this->input('type', 'appointments');
        $file = (new ReportService())->exportCsv($type);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }
}
