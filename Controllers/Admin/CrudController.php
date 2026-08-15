<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;

class CrudController extends Controller
{
    public function customers(): void
    {
        $page = (int) $this->input('page', 1);
        $this->view('admin/customers', [
            'title' => 'Customers',
            'result' => User::customers($page),
        ], 'admin');
    }

    public function customerHistory(string $id): void
    {
        $customer = User::find((int) $id);
        if (!$customer || $customer['role'] !== 'customer') {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Not Found'], 'admin');
            return;
        }

        $appointments = Database::fetchAll(
            "SELECT a.*, s.name AS staff_name
             FROM appointments a
             LEFT JOIN users s ON s.id = a.staff_id
             WHERE a.customer_id = ?
             ORDER BY a.appointment_date DESC, a.start_time DESC",
            [$id]
        );

        foreach ($appointments as &$appt) {
            $appt['services'] = Database::fetchAll(
                "SELECT sv.name, asv.price, asv.duration_minutes
                 FROM appointment_services asv
                 JOIN services sv ON sv.id = asv.service_id
                 WHERE asv.appointment_id = ?",
                [$appt['id']]
            );
        }
        unset($appt);

        $payments = Database::fetchAll(
            "SELECT * FROM payments WHERE customer_id = ? ORDER BY id DESC",
            [$id]
        );
        $invoices = Database::fetchAll(
            "SELECT * FROM invoices WHERE customer_id = ? ORDER BY id DESC",
            [$id]
        );
        $reviews = Database::fetchAll(
            "SELECT * FROM reviews WHERE customer_id = ? ORDER BY id DESC",
            [$id]
        );
        $loyalty = Database::fetchAll(
            "SELECT * FROM loyalty_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 50",
            [$id]
        );

        $stats = [
            'visits' => count($appointments),
            'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'Completed')),
            'spent' => (float) (Database::fetch(
                "SELECT COALESCE(SUM(amount),0) t FROM payments WHERE customer_id = ? AND status = 'Paid'",
                [$id]
            )['t'] ?? 0),
            'cancelled' => count(array_filter($appointments, fn($a) => $a['status'] === 'Cancelled')),
            'last_visit' => $appointments[0]['appointment_date'] ?? null,
            'favorite_service' => Database::fetch(
                "SELECT sv.name, COUNT(*) c
                 FROM appointment_services asv
                 JOIN appointments a ON a.id = asv.appointment_id
                 JOIN services sv ON sv.id = asv.service_id
                 WHERE a.customer_id = ?
                 GROUP BY sv.id, sv.name
                 ORDER BY c DESC LIMIT 1",
                [$id]
            ),
        ];

        $this->view('admin/customer_history', [
            'title' => 'Customer History',
            'customer' => $customer,
            'appointments' => $appointments,
            'payments' => $payments,
            'invoices' => $invoices,
            'reviews' => $reviews,
            'loyalty' => $loyalty,
            'stats' => $stats,
        ], 'admin');
    }

    public function staff(): void
    {
        $staff = Database::fetchAll("SELECT * FROM users WHERE role IN ('staff','admin') ORDER BY role, name");
        $this->view('admin/staff', ['title' => 'Staff', 'staff' => $staff], 'admin');
    }

    public function staffStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $id = $this->input('id');
        $data = [
            'name' => $this->input('name'),
            'phone' => format_phone((string) $this->input('phone')),
            'email' => $this->input('email'),
            'role' => $this->input('role', 'staff'),
            'is_active' => (int) $this->input('is_active', 1),
        ];
        if ($pwd = $this->input('password')) {
            $data['password'] = password_hash((string) $pwd, PASSWORD_BCRYPT);
        }
        if ($id) {
            User::update((int) $id, $data);
        } else {
            $data['uuid'] = User::uuid();
            User::create($data);
        }
        $this->json(['success' => true, 'message' => 'Staff saved.']);
    }

    public function services(): void
    {
        $this->view('admin/services', [
            'title' => 'Services',
            'services' => Service::all('category_id, sort_order'),
            'categories' => Service::categories(),
        ], 'admin');
    }

    public function serviceStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $id = $this->input('id');
        $name = (string) $this->input('name');
        $data = [
            'category_id' => (int) $this->input('category_id'),
            'name' => $name,
            'slug' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? ''),
            'description' => $this->input('description'),
            'duration_minutes' => (int) $this->input('duration_minutes', 30),
            'price' => (float) $this->input('price'),
            'discount_price' => $this->input('discount_price') !== '' && $this->input('discount_price') !== null
                ? (float) $this->input('discount_price') : null,
            'is_featured' => (int) $this->input('is_featured', 0),
            'is_active' => (int) $this->input('is_active', 1),
        ];
        if ($id) {
            Service::update((int) $id, $data);
        } else {
            Service::create($data);
        }
        $this->json(['success' => true, 'message' => 'Service saved.']);
    }

    public function serviceDelete(string $id): void
    {
        Service::update((int) $id, ['is_active' => 0]);
        $this->json(['success' => true, 'message' => 'Service deactivated.']);
    }

    public function appointments(): void
    {
        $status = $this->input('status');
        $page = (int) $this->input('page', 1);
        $result = Appointment::adminList($page, $status ? (string) $status : null);
        foreach ($result['data'] as &$row) {
            $c = Database::fetch('SELECT name, phone FROM users WHERE id=?', [$row['customer_id']]);
            $row['customer_name'] = $c['name'] ?? '';
            $row['customer_phone'] = $c['phone'] ?? '';
        }
        unset($row);
        $this->view('admin/appointments', [
            'title' => 'Appointments',
            'result' => $result,
            'status' => $status,
        ], 'admin');
    }

    public function appointmentStatus(string $id): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        (new BookingService())->updateStatus((int) $id, (string) $this->input('status'), $this->input('reason'));
        $this->json(['success' => true, 'message' => 'Status updated.']);
    }

    public function invoices(): void
    {
        $rows = Database::fetchAll(
            "SELECT i.*, u.name AS customer_name FROM invoices i JOIN users u ON u.id=i.customer_id ORDER BY i.id DESC LIMIT 100"
        );
        $this->view('admin/invoices', ['title' => 'Invoices', 'invoices' => $rows], 'admin');
    }

    public function offers(): void
    {
        $this->view('admin/offers', [
            'title' => 'Offers',
            'offers' => Database::fetchAll('SELECT * FROM offers ORDER BY id DESC'),
        ], 'admin');
    }

    public function offerStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $id = $this->input('id');
        $data = [
            $this->input('title'),
            strtoupper((string) $this->input('code')),
            $this->input('description'),
            $this->input('discount_type', 'percent'),
            (float) $this->input('discount_value'),
            (float) $this->input('min_amount', 0),
            (int) $this->input('is_active', 1),
        ];
        if ($id) {
            Database::query(
                'UPDATE offers SET title=?, code=?, description=?, discount_type=?, discount_value=?, min_amount=?, is_active=? WHERE id=?',
                [...$data, $id]
            );
        } else {
            Database::insert(
                'INSERT INTO offers (title, code, description, discount_type, discount_value, min_amount, is_active) VALUES (?,?,?,?,?,?,?)',
                $data
            );
        }
        $this->json(['success' => true, 'message' => 'Offer saved.']);
    }

    public function gallery(): void
    {
        $this->view('admin/gallery', [
            'title' => 'Gallery',
            'items' => Database::fetchAll('SELECT * FROM gallery ORDER BY sort_order'),
        ], 'admin');
    }

    public function reviews(): void
    {
        $rows = Database::fetchAll(
            "SELECT r.*, u.name AS customer_name FROM reviews r JOIN users u ON u.id=r.customer_id ORDER BY r.id DESC"
        );
        $this->view('admin/reviews', ['title' => 'Reviews', 'reviews' => $rows], 'admin');
    }

    public function reviewModerate(string $id): void
    {
        Database::query('UPDATE reviews SET is_approved=? WHERE id=?', [(int) $this->input('is_approved', 1), $id]);
        $this->json(['success' => true]);
    }

    public function attendance(): void
    {
        $rows = Database::fetchAll(
            "SELECT a.*, u.name AS staff_name FROM attendance a JOIN users u ON u.id=a.staff_id ORDER BY a.date DESC LIMIT 100"
        );
        $this->view('admin/attendance', ['title' => 'Attendance', 'rows' => $rows, 'staff' => User::staffList()], 'admin');
    }

    public function attendanceStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        Database::query(
            'INSERT INTO attendance (staff_id, date, check_in, check_out, status, notes)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE check_in=VALUES(check_in), check_out=VALUES(check_out), status=VALUES(status), notes=VALUES(notes)',
            [
                (int) $this->input('staff_id'),
                $this->input('date'),
                $this->input('check_in'),
                $this->input('check_out'),
                $this->input('status', 'Present'),
                $this->input('notes'),
            ]
        );
        $this->json(['success' => true, 'message' => 'Attendance saved.']);
    }

    public function salaries(): void
    {
        $rows = Database::fetchAll(
            "SELECT s.*, u.name AS staff_name FROM salaries s JOIN users u ON u.id=s.staff_id ORDER BY s.year DESC, s.month DESC"
        );
        $this->view('admin/salaries', ['title' => 'Salaries', 'rows' => $rows, 'staff' => User::staffList()], 'admin');
    }

    public function salaryStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $base = (float) $this->input('base_salary');
        $bonus = (float) $this->input('bonus', 0);
        $ded = (float) $this->input('deductions', 0);
        Database::query(
            'INSERT INTO salaries (staff_id, month, year, base_salary, bonus, deductions, net_salary, status)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE base_salary=VALUES(base_salary), bonus=VALUES(bonus), deductions=VALUES(deductions), net_salary=VALUES(net_salary), status=VALUES(status)',
            [
                (int) $this->input('staff_id'),
                (int) $this->input('month'),
                (int) $this->input('year'),
                $base, $bonus, $ded, $base + $bonus - $ded,
                $this->input('status', 'Pending'),
            ]
        );
        $this->json(['success' => true, 'message' => 'Salary saved.']);
    }

    public function expenses(): void
    {
        $this->view('admin/expenses', [
            'title' => 'Expenses',
            'rows' => Database::fetchAll('SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 100'),
        ], 'admin');
    }

    public function expenseStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        Database::insert(
            'INSERT INTO expenses (title, category, amount, expense_date, notes, created_by) VALUES (?,?,?,?,?,?)',
            [
                $this->input('title'),
                $this->input('category'),
                (float) $this->input('amount'),
                $this->input('expense_date'),
                $this->input('notes'),
                Session::user()['id'] ?? null,
            ]
        );
        $this->json(['success' => true, 'message' => 'Expense recorded.']);
    }

    public function inventory(): void
    {
        $this->view('admin/inventory', [
            'title' => 'Inventory',
            'rows' => Database::fetchAll('SELECT * FROM inventory ORDER BY name'),
        ], 'admin');
    }

    public function inventoryStore(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $id = $this->input('id');
        $data = [
            $this->input('name'),
            $this->input('sku'),
            $this->input('category'),
            (int) $this->input('quantity'),
            $this->input('unit', 'pcs'),
            (int) $this->input('reorder_level', 5),
            (float) $this->input('unit_cost', 0),
            $this->input('supplier'),
        ];
        if ($id) {
            Database::query(
                'UPDATE inventory SET name=?, sku=?, category=?, quantity=?, unit=?, reorder_level=?, unit_cost=?, supplier=? WHERE id=?',
                [...$data, $id]
            );
        } else {
            Database::insert(
                'INSERT INTO inventory (name, sku, category, quantity, unit, reorder_level, unit_cost, supplier) VALUES (?,?,?,?,?,?,?,?)',
                $data
            );
        }
        $this->json(['success' => true, 'message' => 'Inventory saved.']);
    }

    public function settings(): void
    {
        $this->view('admin/settings', [
            'title' => 'Settings',
            'settings' => Database::fetchAll('SELECT * FROM settings ORDER BY group_name, setting_key'),
        ], 'admin');
    }

    public function settingsSave(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $settings = $this->input('settings');
        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                Database::query('UPDATE settings SET setting_value=? WHERE setting_key=?', [$value, $key]);
            }
        }
        $this->json(['success' => true, 'message' => 'Settings saved.']);
    }
}
