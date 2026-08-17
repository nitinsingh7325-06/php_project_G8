<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class StaffMiddleware
{
    public function handle(): bool
    {
        $user = Session::user();
        if (!$user || !in_array($user['role'], ['staff', 'admin'], true)) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Staff access required']);
                return false;
            }
            header('Location: ' . url('login'));
            return false;
        }
        return true;
    }
}
