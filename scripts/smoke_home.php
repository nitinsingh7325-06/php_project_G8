<?php

$_SERVER['REQUEST_URI'] = '/wave/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/wave/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
require dirname(__DIR__) . '/index.php';
$out = ob_get_clean();

echo (str_contains($out, 'The Wave') ? "HOME_OK\n" : "HOME_FAIL\n");
echo 'bytes=' . strlen($out) . "\n";
if (!str_contains($out, 'The Wave')) {
    echo substr($out, 0, 500);
}
