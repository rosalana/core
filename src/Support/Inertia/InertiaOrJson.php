<?php

namespace Rosalana\Core\Support\Inertia;

class InertiaOrJson
{
    public static function render($component, $props = [])
    {
        if (request()->header('X-Inertia')) {
            return inertia($component, $props);
        }

        return response()->json($props);
    }
}