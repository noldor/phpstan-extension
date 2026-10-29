<?php

defined('LARAVEL_VERSION') || define('LARAVEL_VERSION', '13.38.0');
$application = new Illuminate\Foundation\Application(__DIR__);
$application->instance('config', new Illuminate\Config\Repository([
    'database' => ['default' => 'pgsql', 'connections' => ['pgsql' => ['driver' => 'pgsql']]],
]));

spl_autoload_register(static function (string $class): void {
    if (! str_starts_with($class, 'EloquentFixture\\')) { return; }
    foreach (['CoreValid', 'CoreInvalid', 'RegistrationsValid', 'RegistrationsInvalid', 'PropertiesValid', 'PropertiesInvalid', 'PivotsValid', 'PivotsInvalid', 'NullabilityInvalid', 'DateOverridesInvalid', 'ReviewInvalid', 'ReviewValid', 'SecondReviewInvalid', 'PublicAccessorsValid', 'SetterOnlyValid', 'SetterOnlyInvalid', 'AccessorFlowValid', 'AccessorFlowInvalid', 'ConstructorSourceValid', 'ConstructorSourceInvalid'] as $file) {
        require_once __DIR__ . '/' . $file . '.php';
    }
});
