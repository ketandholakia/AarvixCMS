<?php

use App\Providers\AppServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    Laravel\Fortify\FortifyServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\PluginServiceProvider::class,
    App\Providers\ThemeServiceProvider::class,
];
