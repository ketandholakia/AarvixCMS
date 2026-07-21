<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Laravel\Fortify\FortifyServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
];
