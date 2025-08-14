<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;


class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'error' => 'No autenticado'
            ], 401);
        }

        $user = auth()->user();
        
        try {
            // Verificar si es admin directamente usando la misma lógica de CheckPermission
            $isAdmin = $user->roles->contains('slug', 'admin');
            
            if (!$isAdmin) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos de administrador'
                ], 403);
            }
            
            return $next($request);
        } catch (\Exception $e) {
            Log::error('Error en verificación de admin: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error en la verificación de permisos'
            ], 500);
        }
    }
}
