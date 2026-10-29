<?php

declare(strict_types = 1);

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

$app = new Application(__DIR__);
$app->instance('config', new Repository([]));
Facade::setFacadeApplication($app);
\defined('LARAVEL_VERSION') || \define('LARAVEL_VERSION', $app->version());
