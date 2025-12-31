<?php

use Rosalana\Core\Services\Actions\Runner;

if (!function_exists('run')) {
    function run(object $action): mixed
    {
        return Runner::run($action);
    }
}
