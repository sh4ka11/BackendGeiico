<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;

class GoogleController extends Controller 
{
    /**
     * Redirige al consent screen de Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Maneja el callback de Google y redirecciona al dashboard con los datos del usuario.
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
            $email = $socialUser->getEmail();
            
            // Buscar o crear usuario según email
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $socialUser->getName(),
                    'google_id' => $socialUser->getId(),
                    'avatar'    => $socialUser->getAvatar(),
                    'password'  => bcrypt(Str::random(24)),
                ]
            );
            
            // Verificar si es el administrador principal
            if ($email === 'dayronhernandezparedes@gmail.com') {
                try {
                    // Obtener o crear el rol de administrador
                    $adminRole = Role::firstOrCreate(
                        ['slug' => 'admin'],
                        ['name' => 'Administrador', 'description' => 'Acceso completo al sistema']
                    );
                    
                    // Cargar la relación roles para evitar errores
                    $user->load('roles');
                    
                    // Comprobar si tiene el rol antes de asignarlo
                    $hasRole = false;
                    foreach ($user->roles as $role) {
                        if ($role->slug === 'admin') {
                            $hasRole = true;
                            break;
                        }
                    }
                    
                    // Asignar rol de administrador si no lo tiene ya
                    if (!$hasRole) {
                        $user->roles()->syncWithoutDetaching([$adminRole->id]);
                        Log::info("Rol de administrador asignado a: {$user->email}");
                    }
                } catch (\Exception $e) {
                    Log::error('Error asignando rol de administrador: ' . $e->getMessage());
                    // Continuar con el proceso de login aunque falle la asignación de rol
                }
            }
            
            // Crear token con Sanctum
            $token = $user->createToken('google-auth')->plainTextToken;
            
            // Incluir info de roles en la respuesta
            $userRoles = [];
            try {
                $user->load('roles');
                $userRoles = $user->roles->pluck('slug')->toArray();
            } catch (\Exception $e) {
                Log::error('Error cargando roles: ' . $e->getMessage());
            }
            
            // Determinar si es admin de forma segura
            $isAdmin = '0';
            try {
                $user->load('roles');
                $isAdmin = $user->roles->contains('slug', 'admin') ? '1' : '0';
            } catch (\Exception $e) {
                Log::error('Error verificando si es admin: ' . $e->getMessage());
            }
            
            // Redirigir al frontend con los datos necesarios
            return redirect()->away(
                env('FRONTEND_URL', 'http://127.0.0.1:8001') . '/dashboard?' . http_build_query([
                    'token'  => $token,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'avatar' => $user->avatar,
                    'roles'  => implode(',', $userRoles),
                    'is_admin' => $isAdmin
                ])
            );
            
        } catch (\Exception $e) {
            Log::error('Error en Google Callback: ' . $e->getMessage());
            // En caso de error, redirigir al login con mensaje de error
            return redirect()->away(
                env('FRONTEND_URL', 'http://127.0.0.1:8001') . '/login?error=' . urlencode('Error al autenticar con Google: ' . $e->getMessage())
            );
        }
    }
    
    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return response()->json([
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        // Eliminar el token actual
        $request->user()->currentAccessToken()->delete();
        
        // Devolver respuesta exitosa
        return response()->json([
            'message' => 'Sesión cerrada correctamente',
            'status' => 'success'
        ]);
    }
}