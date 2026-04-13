<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            ! $request->user()?->isSuperAdmin(),
            403,
            'Se requieren permisos de super administrador.'
        );

        return $next($request);
    }
}
