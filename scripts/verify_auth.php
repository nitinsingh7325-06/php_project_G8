<?php
require __DIR__ . '/../config/bootstrap.php';
$checks = [
    ['contactwavemenssalon@gmail.com', '@wavesalon'],
    ['rahul@thewavemenssalon.com', 'Staff@123'],
    ['demo@example.com', 'Customer@123'],
];
foreach ($checks as [$email, $pass]) {
    $u = App\Models\User::findByEmail($email);
    $ok = $u && password_verify($pass, $u['password']);
    echo $email . ' => ' . ($ok ? "OK\n" : "FAIL\n");
}
$img = dirname(__DIR__) . '/public/assets/img/salon-bg.png';
echo 'image_exists=' . (file_exists($img) ? 'yes' : 'no') . ' size=' . filesize($img) . "\n";
