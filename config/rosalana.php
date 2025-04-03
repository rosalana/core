<?php

return [
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Rosalana Core Installed
    |--------------------------------------------------------------------------
    |
    | All installed Rosalana packages. This array is used to determine if the
    | package has been installed or not and with the correct version.
    |
    | DO NOT MODIFY THIS ARRAY MANUALLY!
    |
    */
    'installed' => [],

];
