<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        $user = auth()->user();
        
        try {
            // Cargar roles y permisos
            // Acceder directamente a las relaciones
            
            // Verificar si es admin
            $isAdmin = $user->roles->contains('slug', 'admin');
            
            // Si es admin, permitir acceso
            if ($isAdmin) {
                return $next($request);
            }
            
            // Verificar si tiene el permiso específico
            $hasPermission = false;
            foreach ($user->roles as $role) {
                foreach ($role->permissions as $perm) {
                    if ($perm->slug === $permission) {
                        $hasPermission = true;
                        break 2;
                    }
                }
            }
            
            // Si tiene el permiso, permitir acceso
            if ($hasPermission) {
                return $next($request);
            }
            
            // Si no tiene los permisos necesarios, denegar acceso
            return $request->expectsJson()
                ? response()->json(['error' => 'Forbidden. Insufficient permissions.'], 403)
                : abort(403, 'No tienes permiso para acceder a esta sección.');
                
        } catch (\Exception $e) {
            Log::error('Error en verificación de permisos: ' . $e->getMessage());
            return $request->expectsJson()
                ? response()->json(['error' => 'Error en verificación de permisos.'], 500)
                : abort(500, 'Error interno al verificar permisos.');
        }
    }
}