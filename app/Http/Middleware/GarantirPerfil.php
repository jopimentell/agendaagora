<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: ->middleware('perfil:admin') */
class GarantirPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && in_array($usuario->perfil, $perfis, true), 403);

        return $next($request);
    }
}
