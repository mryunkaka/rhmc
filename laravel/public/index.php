<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// -----------------------------------------------------------------------------
// FIX: Some Apache/PHP environments define APP_* / DB_* env vars globally.
// Laravel uses an immutable env repository, so `.env` will NOT override them.
// That can cause intermittent defaults like APP_ENV=production / DB=sqlite and
// MissingAppKeyException. Preload `.env` using a *mutable* repository so `.env`
// always wins for this app.
// -----------------------------------------------------------------------------
if (! file_exists(__DIR__.'/../bootstrap/cache/config.php') && is_file(__DIR__.'/../.env')) {
    try {
        $repository = Dotenv\Repository\RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(Dotenv\Repository\Adapter\PutenvAdapter::class)
            ->make();

        Dotenv\Dotenv::create($repository, dirname(__DIR__), '.env')->safeLoad();
    } catch (Throwable) {
        // If dotenv fails here, Laravel will still attempt to load it later.
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
