<?php

declare(strict_types=1);

/**
 * Import schema + seed using the mysql CLI when available,
 * otherwise PDO multi-statement execution.
 */
$root = dirname(__DIR__);
require $root . '/config/bootstrap.php';

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');

$mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
if (!file_exists($mysql)) {
    $mysql = 'mysql';
}

foreach (['database.sql'] as $file) {
    $path = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $file;
    echo "Importing {$file}...\n";
    $cmd = sprintf(
        '"%s" -h %s -P %s -u %s %s --default-character-set=utf8mb4 < "%s"',
        $mysql,
        escapeshellarg($host),
        escapeshellarg((string) $port),
        escapeshellarg($user),
        $pass !== '' ? '-p' . escapeshellarg($pass) : '',
        $path
    );
    // Prefer proc_open with stdin for Windows
    $descriptors = [
        0 => ['file', $path, 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $args = [$mysql, '-h', $host, '-P', (string) $port, '-u', $user, '--default-character-set=utf8mb4'];
    if ($pass !== '') {
        $args[] = '-p' . $pass;
    }
    $proc = proc_open($args, $descriptors, $pipes, $root);
    if (is_resource($proc)) {
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            echo "  error: {$err}\n";
            exit(1);
        }
        echo "  OK\n";
    } else {
        echo "  Failed to start mysql client\n";
        exit(1);
    }
}

echo "Database ready: wave_salon\n";
echo "Admin phone: +919999000001 — OTP logged to storage/logs/app.log when SMS_PROVIDER=log\n";
