<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        putenv('APP_ENV=testing');
        putenv('APP_CONFIG_CACHE=bootstrap/cache/config-testing.php');
        putenv('APP_EVENTS_CACHE=bootstrap/cache/events-testing.php');
        putenv('APP_PACKAGES_CACHE=bootstrap/cache/packages-testing.php');
        putenv('APP_ROUTES_CACHE=bootstrap/cache/routes-testing.php');
        putenv('APP_SERVICES_CACHE=bootstrap/cache/services-testing.php');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('SESSION_DRIVER=array');

        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        $_ENV['APP_CONFIG_CACHE'] = $_SERVER['APP_CONFIG_CACHE'] = 'bootstrap/cache/config-testing.php';
        $_ENV['APP_EVENTS_CACHE'] = $_SERVER['APP_EVENTS_CACHE'] = 'bootstrap/cache/events-testing.php';
        $_ENV['APP_PACKAGES_CACHE'] = $_SERVER['APP_PACKAGES_CACHE'] = 'bootstrap/cache/packages-testing.php';
        $_ENV['APP_ROUTES_CACHE'] = $_SERVER['APP_ROUTES_CACHE'] = 'bootstrap/cache/routes-testing.php';
        $_ENV['APP_SERVICES_CACHE'] = $_SERVER['APP_SERVICES_CACHE'] = 'bootstrap/cache/services-testing.php';
        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
        $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
