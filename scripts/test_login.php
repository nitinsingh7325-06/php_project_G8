<?php

declare(strict_types=1);

/**
 * End-to-end login test (CLI simulation)
 */
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/wave/auth/login';
$_SERVER['SCRIPT_NAME'] = '/wave/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['HTTPS'] = 'off';

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/bootstrap.php';

use App\Core\Session;
use App\Models\User;

Session::start();
$csrf = Session::csrf();

$results = [];

// Verify password hashes first
$checks = [
    ['contactwavemenssalon@gmail.com', '@wavesalon'],
    ['rahul@thewavemenssalon.com', 'Staff@123'],
    ['demo@example.com', 'Customer@123'],
];

foreach ($checks as [$email, $pass]) {
    $u = User::findByEmail($email);
    if (!$u) {
        $results[] = "MISSING_USER {$email}";
        continue;
    }
    $results[] = $email . ' => ' . (password_verify($pass, $u['password']) ? 'PASS_OK' : 'PASS_FAIL');
}

$_POST['_csrf'] = $csrf;
$_POST['login'] = 'contactwavemenssalon@gmail.com';
$_POST['password'] = '@wavesalon';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

$controller = new App\Controllers\AuthController();

ob_start();
try {
    $controller->login();
} catch (Throwable $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . "\n";
}
$out = ob_get_clean();

foreach ($results as $res) {
    echo $res . "\n";
}
echo "LOGIN_RESPONSE={$out}\n";

$user = Session::user();
echo 'SESSION_USER=' . ($user['email'] ?? 'none') . ' ROLE=' . ($user['role'] ?? 'none') . "\n";
