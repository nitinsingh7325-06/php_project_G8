<?php
$_SERVER['REQUEST_URI'] = '/wave/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/wave/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_ENV['APP_URL'] = 'http://localhost/wave';
$_SERVER['APP_URL'] = 'http://localhost/wave';

require dirname(__DIR__) . '/config/bootstrap.php';

echo 'asset=' . asset('css/app.css') . PHP_EOL;
echo 'url=' . url('login') . PHP_EOL;

ob_start();
require dirname(__DIR__) . '/index.php';
$html = ob_get_clean();

if (preg_match('/href="([^"]*app\.css[^"]*)"/', $html, $m)) {
    echo 'css_href=' . $m[1] . PHP_EOL;
    echo (str_contains($m[1], '/wave/assets/') || str_contains($m[1], '/assets/')) ? 'CSS_PATH_OK' . PHP_EOL : 'CSS_PATH_BAD' . PHP_EOL;
} else {
    echo "CSS_LINK_MISSING\n";
}
echo (str_contains($html, 'salon-bg') ? 'BODY_CLASS_OK' : 'BODY_CLASS_BAD') . PHP_EOL;
