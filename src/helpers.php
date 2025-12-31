<?php

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Services\Actions\Runner;

if (!function_exists('run')) {
    function run(Action $action): mixed
    {
        return Runner::run($action);
    }
}
