<?php

declare(strict_types=1);

/**
 * Front controller — The Wave Men's Salon
 * Root entry point for standard web servers & shared hosting (InfinityFree).
 */

define('BASE_PATH', __DIR__);

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    require BASE_PATH . '/config/bootstrap.php';
}

use App\Core\App;
use App\Core\Session;

Session::start();

$app = App::getInstance();
$router = $app->router();

require BASE_PATH . '/routes/web.php';

$app->run();
