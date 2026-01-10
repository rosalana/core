<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rosalana Internal API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically registered by the Rosalana Core package.
| They are protected by ForceJson and RevizorCheckTicket middleware.
|
| Internal routes are used for App2App communication via Basecamp facade.
|
*/

Route::post('/ping', function () {
    return ok(['pong' => true])();
});
