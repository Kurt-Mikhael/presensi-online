<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

if (! file_exists($autoload = __DIR__.'/../vendor/autoload.php')) {
    http_response_code(500);
    fwrite(STDERR, "[public/index] vendor/autoload.php tidak ditemukan. Jalankan 'composer install'.\n");
    require __DIR__.'/../storage/framework/views/offline.html';
    exit(1);
}
require $autoload;

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);