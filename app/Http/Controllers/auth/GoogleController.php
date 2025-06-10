<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Models\User;

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
            
            // Buscar o crear usuario según email
            $user = User::updateOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name'      => $socialUser->getName(),
                    'google_id' => $socialUser->getId(),
                    'avatar'    => $socialUser->getAvatar(),
                    'password'  => bcrypt(Str::random(24)),
                ]
            );
            
            // Crear token con Sanctum
            $token = $user->createToken('google-auth')->plainTextToken;
            
            // Redirigir al frontend con los datos necesarios
            return redirect()->away(
                env('FRONTEND_URL', 'http://127.0.0.1:8001') . '/dashboard?' . http_build_query([
                    'token'  => $token,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'avatar' => $user->avatar,
                ])
            );
            
        } catch (\Exception $e) {
            // En caso de error, redirigir al login con mensaje de error
            return redirect()->away(
                env('FRONTEND_URL', 'http://127.0.0.1:8001') . '/login?error=' . urlencode('Error al autenticar con Google: ' . $e->getMessage())
            );
        }
    }
     /**
     * Cierra la sesión del usuario.
     * 
     * Revoca todos los tokens del usuario autenticado
     * y devuelve una respuesta JSON indicando el éxito.
     */
    public function logout(Request $request)
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return response()->json([
                'message' => 'No hay sesión activa'
            ], 401);
        }
        
        // Obtener el usuario autenticado
        $user = auth()->user();
        
        // Eliminar todos los tokens del usuario (logout completo de todos los dispositivos)
        // Si se quiere eliminar solo el token actual, se puede usar $request->user()->currentAccessToken()->delete();
        $request->user()->currentAccessToken()->delete();
        
        // Devolver respuesta exitosa
        return response()->json([
            'message' => 'Sesión cerrada correctamente',
            'status' => 'success'
        ]);
    }
}

    
    /**
     * Cierra la sesión del usuario.
     */
//     public function logout()
//     {
//         $user = auth()->user();
    
//         if ($user && method_exists($user, 'tokens')) {
//             $user->tokens()->delete();
//         }
    
//         return response()->json([
//             'message' => 'Sesión cerrada correctamente'
//         ]);
//     }
// }