<?php

declare(strict_types=1);

use App\Controllers\Admin\CrudController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\HomeController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\ReviewController;
use App\Controllers\Staff\StaffController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\StaffMiddleware;

/** @var \App\Core\Router $router */

// Public pages
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/services', [HomeController::class, 'services']);
$router->get('/pricing', [HomeController::class, 'pricing']);
$router->get('/gallery', [HomeController::class, 'gallery']);
$router->get('/staff', [HomeController::class, 'staff']);
$router->get('/reviews', [HomeController::class, 'reviews']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'contactSubmit']);

// Auth (password-based)
$router->get('/login', [AuthController::class, 'loginForm']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Booking
$router->get('/book', [BookingController::class, 'index']);
$router->get('/api/availability', [BookingController::class, 'availability']);
$router->post('/api/book', [BookingController::class, 'store']);
$router->post('/api/payments/mark-paid', [PaymentController::class, 'markPaid']);
$router->post('/webhooks/payments', [PaymentController::class, 'webhook']);

// Customer (auth)
$router->get('/dashboard', [BookingController::class, 'dashboard'], [AuthMiddleware::class]);
$router->get('/profile', [AuthController::class, 'profile'], [AuthMiddleware::class]);
$router->post('/profile', [AuthController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->get('/appointments', [BookingController::class, 'history'], [AuthMiddleware::class]);
$router->get('/appointments/{bookingId}', [BookingController::class, 'show'], [AuthMiddleware::class]);
$router->post('/appointments/{id}/cancel', [BookingController::class, 'cancel'], [AuthMiddleware::class]);
$router->post('/reviews', [ReviewController::class, 'store'], [AuthMiddleware::class]);

// Invoices
$router->post('/api/invoices/generate', [InvoiceController::class, 'generate'], [AuthMiddleware::class]);
$router->get('/invoices/{id}', [InvoiceController::class, 'show'], [AuthMiddleware::class]);

// Staff Portal
$staffMid = [AuthMiddleware::class, StaffMiddleware::class];
$router->get('/staff/dashboard', [StaffController::class, 'dashboard'], $staffMid);
$router->post('/staff/appointments/{id}/status', [StaffController::class, 'updateStatus'], $staffMid);
$router->post('/staff/attendance/mark', [StaffController::class, 'markAttendance'], $staffMid);

// Admin
$admin = [AuthMiddleware::class, AdminMiddleware::class];
$router->get('/admin', [DashboardController::class, 'index'], $admin);
$router->get('/admin/chart', [DashboardController::class, 'chartData'], $admin);
$router->get('/admin/export', [DashboardController::class, 'export'], $admin);
$router->get('/admin/customers', [CrudController::class, 'customers'], $admin);
$router->get('/admin/customers/{id}/history', [CrudController::class, 'customerHistory'], $admin);
$router->get('/admin/staff', [CrudController::class, 'staff'], $admin);
$router->post('/admin/staff', [CrudController::class, 'staffStore'], $admin);
$router->get('/admin/services', [CrudController::class, 'services'], $admin);
$router->post('/admin/services', [CrudController::class, 'serviceStore'], $admin);
$router->post('/admin/services/{id}/delete', [CrudController::class, 'serviceDelete'], $admin);
$router->get('/admin/appointments', [CrudController::class, 'appointments'], $admin);
$router->post('/admin/appointments/{id}/status', [CrudController::class, 'appointmentStatus'], $admin);
$router->get('/admin/invoices', [CrudController::class, 'invoices'], $admin);
$router->get('/admin/offers', [CrudController::class, 'offers'], $admin);
$router->post('/admin/offers', [CrudController::class, 'offerStore'], $admin);
$router->get('/admin/gallery', [CrudController::class, 'gallery'], $admin);
$router->get('/admin/reviews', [CrudController::class, 'reviews'], $admin);
$router->post('/admin/reviews/{id}', [CrudController::class, 'reviewModerate'], $admin);
$router->get('/admin/attendance', [CrudController::class, 'attendance'], $admin);
$router->post('/admin/attendance', [CrudController::class, 'attendanceStore'], $admin);
$router->get('/admin/salaries', [CrudController::class, 'salaries'], $admin);
$router->post('/admin/salaries', [CrudController::class, 'salaryStore'], $admin);
$router->get('/admin/expenses', [CrudController::class, 'expenses'], $admin);
$router->post('/admin/expenses', [CrudController::class, 'expenseStore'], $admin);
$router->get('/admin/inventory', [CrudController::class, 'inventory'], $admin);
$router->post('/admin/inventory', [CrudController::class, 'inventoryStore'], $admin);
$router->get('/admin/settings', [CrudController::class, 'settings'], $admin);
$router->post('/admin/settings', [CrudController::class, 'settingsSave'], $admin);
