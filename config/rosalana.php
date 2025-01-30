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

];
