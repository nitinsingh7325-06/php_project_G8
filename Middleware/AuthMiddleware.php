<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Session::isLoggedIn()) {
            if ($this->wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
                return false;
            }
            Session::flash('error', 'Please login to continue.');
            header('Location: ' . url('login'));
            return false;
        }
        return true;
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json') || strtolower($xhr) === 'xmlhttprequest';
    }
}
