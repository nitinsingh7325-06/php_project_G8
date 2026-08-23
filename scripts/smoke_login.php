<?php
$_SERVER['REQUEST_URI'] = '/wave/login';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/wave/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
ob_start();
require dirname(__DIR__) . '/index.php';
$out = ob_get_clean();
echo (str_contains($out, 'Sign in') || str_contains($out, 'Login') ? "LOGIN_OK\n" : "LOGIN_FAIL\n");
echo 'bytes=' . strlen($out) . "\n";
