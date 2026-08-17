<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class AdminMiddleware
{
    public function handle(): bool
    {
        $user = Session::user();
        if (!$user) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return false;
            }
            header('Location: ' . url('login'));
            return false;
        }

        if ($user['role'] === 'staff') {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                return false;
            }
            header('Location: ' . url('staff/dashboard'));
            return false;
        }

        if ($user['role'] !== 'admin') {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                return false;
            }
            header('Location: ' . url('dashboard'));
            return false;
        }

        return true;
    }
}
