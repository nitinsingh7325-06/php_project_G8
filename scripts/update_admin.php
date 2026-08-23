<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/bootstrap.php';

use App\Core\Database;

$email = 'contactwavemenssalon@gmail.com';
$password = '@wavesalon';
$hash = password_hash($password, PASSWORD_BCRYPT);

Database::query(
    "UPDATE users SET email = ?, password = ? WHERE role = 'admin' OR email = 'admin@thewavemenssalon.com'",
    [$email, $hash]
);

echo "Admin account successfully updated!\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
