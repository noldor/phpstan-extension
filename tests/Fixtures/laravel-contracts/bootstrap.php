<?php

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\DatabaseServiceProvider;

$app = new Application(__DIR__);
$app->instance('config', new Repository(['database' => ['default' => 'analysis', 'connections' => ['analysis' => ['driver' => 'pgsql', 'database' => 'analysis']]]]));
$app->register(DatabaseServiceProvider::class);
$app->boot();
Facade::setFacadeApplication($app);

define('LARAVEL_VERSION', $app->version());
