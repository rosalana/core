<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rosalana Event Settings
    |--------------------------------------------------------------------------
    |
    | Definuje queue připojení a název fronty pro lokální a globální eventy.
    | Předpokládá se, že hostitelská aplikace v config/queue.php
    | definuje "local-db" a "global-rabbit" (nebo cokoliv jiného).
    |
    */
    'events' => [
        'local_connection' => env('ROSALANA_LOCAL_CONNECTION', 'database'),
        'local_queue' => env('ROSALANA_LOCAL_QUEUE', 'default'),

        'global_connection' => env('ROSALANA_GLOBAL_CONNECTION', 'redis'),
        'global_queue' => env('ROSALANA_GLOBAL_QUEUE', 'global'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rosalana Basecamp Auth Settings
    |--------------------------------------------------------------------------
    |
    | Here you can define the settings for the Rosalana Auth.
    | This settings are used for authorizate your app to the Rosalana Basecamp
    | to use Basecamp services.
    |
    */
    'basecamp' => [
        'url' => env('ROSALANA_BASECAMP_URL', 'http://localhost:8000'),
        'secret' => env('ROSALANA_APP_SECRET', 'secret'),
        'origin' => env('FRONTEND_URL', 'http://localhost:3000'),
    ],

];
