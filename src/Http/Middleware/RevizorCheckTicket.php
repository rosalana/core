<?php

namespace Rosalana\Core\Http\Middleware;

use Illuminate\Http\Request;
use Rosalana\Core\Facades\Revizor;
use Symfony\Component\HttpFoundation\Response;

class RevizorCheckTicket
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        try {
            Revizor::verifyRequest();
        } catch (\Exception $e) {
            return error()->unauthorized($e->getMessage())();
        }

        return $next($request);
    }
}
