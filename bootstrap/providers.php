<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;

// laravel/telescope is a require-dev package. A production deploy that runs
// `composer install --no-dev` won't have it in vendor/, so this provider —
// which extends a class from that package — must only be registered when the
// package is actually present, or every request fatal-errors on boot.
return array_values(array_filter([
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class) ? TelescopeServiceProvider::class : null,
]));
