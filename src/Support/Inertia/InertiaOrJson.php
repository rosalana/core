<?php

namespace Rosalana\Core\Support\Inertia;

class InertiaOrJson
{
    /**
     * @return \Inertia\Response|\Illuminate\Http\JsonResponse
     */
    public static function render($component, $props = [])
    {
        if (request()->wantsJson()) {
            if (request()->header('X-Inertia')) {
                return inertia($component, $props);
            }
            return response()->json($props);
        }

        return inertia($component, $props);
    }
}
