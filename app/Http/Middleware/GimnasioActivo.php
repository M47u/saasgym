<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to all gym-tenant routes.
 * - Blocks super_admin (wrong endpoint set).
 * - Blocks users whose gimnasio has been deactivated.
 */
class GimnasioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            abort(403, 'Esta ruta es exclusiva de usuarios de gimnasio.');
        }

        // Eager-load to avoid an extra query per request
        if (! $user->relationLoaded('gimnasio')) {
            $user->load('gimnasio');
        }

        abort_if(
            $user->gimnasio === null || ! $user->gimnasio->activo,
            403,
            'El gimnasio está desactivado. Contactá al administrador.'
        );

        return $next($request);
    }
}
