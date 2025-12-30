<?php

if (!function_exists('run')) {
    function run(object $action): mixed
    {
        return event(new \Rosalana\Core\Services\Actions\Event($action));
    }
}
