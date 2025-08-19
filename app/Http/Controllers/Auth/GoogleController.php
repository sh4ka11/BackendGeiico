<?php


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;

class GoogleController extends Controller 
{
    /**
     * Redirige al usuario a Google para autenticación.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback de Google OAuth que devuelve una vista Blade con los datos.
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
            $email = $socialUser->getEmail();

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $socialUser->getName(),
                    'google_id' => $socialUser->getId(),
                    'avatar'    => $socialUser->getAvatar(),
                    'password'  => bcrypt(Str::random(24)),
                ]
            );

            // Asigna rol de administrador si corresponde
            if ($email === 'dayronhernandezparedes@gmail.com') {
                $adminRole = Role::firstOrCreate(
                    ['slug' => 'admin'],
                    ['name' => 'Administrador', 'description' => 'Acceso completo al sistema']
                );
                if (!$user->roles->contains('slug', 'admin')) {
                    $user->roles()->syncWithoutDetaching([$adminRole->id]);
                }
            }

            $token = $user->createToken('google-auth')->plainTextToken;

            // Devuelve la vista Blade con los datos
            return view('auth.native-callback', [
                'authData' => [
                    'token' => $token,
                    'user'  => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'avatar'=> $user->avatar,
                        'roles' => $user->roles->pluck('slug'),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en Google Callback: ' . $e->getMessage());
            return view('auth.native-callback', [
                'authData' => [
                    'error' => 'Error al autenticar con Google'
                ]
            ]);
        }
    }
}