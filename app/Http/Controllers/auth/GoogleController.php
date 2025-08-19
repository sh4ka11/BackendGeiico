<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;

class GoogleController extends Controller 
{
    /**
     * /login-google
     * Soporta modo escritorio con ?desktop=1&return_uri=geiico://auth-callback o ?response=json
     */
    public function redirectToGoogle(Request $request)
    {
        $redirectUri = $request->getSchemeAndHttpHost() . '/google-callback';

        session([
            'oauth.desktop'    => $request->boolean('desktop'),
            'oauth.return_uri' => $request->query('return_uri'),
            'oauth.response'   => $request->query('response', 'auto'),
        ]);

        // Sobrescribe el redirect en runtime (sin stateless)
        config(['services.google.redirect' => $redirectUri]);

        // Importante: sin ->with() para evitar conflictos de versión
        return Socialite::driver('google')->redirect();
    }

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

            if ($email === 'dayronhernandezparedes@gmail.com') {
                try {
                    $adminRole = Role::firstOrCreate(
                        ['slug' => 'admin'],
                        ['name' => 'Administrador', 'description' => 'Acceso completo al sistema']
                    );
                    $user->load('roles');
                    $hasRole = false;
                    foreach ($user->roles as $role) {
                        if ($role->slug === 'admin') { $hasRole = true; break; }
                    }
                    if (!$hasRole) {
                        $user->roles()->syncWithoutDetaching([$adminRole->id]);
                        Log::info("Rol de administrador asignado a: {$user->email}");
                    }
                } catch (\Exception $e) {
                    Log::error('Error asignando rol de administrador: ' . $e->getMessage());
                }
            }

            $token = $user->createToken('google-auth')->plainTextToken;

            $userRoles = [];
            try {
                $user->load('roles');
                $userRoles = $user->roles->pluck('slug')->toArray();
            } catch (\Exception $e) {
                Log::error('Error cargando roles: ' . $e->getMessage());
            }

            $isAdmin = '0';
            try {
                $user->load('roles');
                $isAdmin = $user->roles->contains('slug', 'admin') ? '1' : '0';
            } catch (\Exception $e) {
                Log::error('Error verificando admin: ' . $e->getMessage());
            }

            $payload = [
                'token'    => $token,
                'name'     => $user->name,
                'email'    => $user->email,
                'avatar'   => $user->avatar,
                'roles'    => implode(',', $userRoles),
                'is_admin' => $isAdmin
            ];

            $isDesktop = (bool) session()->pull('oauth.desktop', false);
            $returnUri = session()->pull('oauth.return_uri');
            $respMode  = session()->pull('oauth.response', 'auto');

            if ($isDesktop) {
                $canDeepLink = $this->isAllowedReturnUri($returnUri);
                if ($respMode === 'deeplink' || ($respMode === 'auto' && $canDeepLink)) {
                    if ($canDeepLink) {
                        return redirect()->away($returnUri . '?' . http_build_query($payload));
                    }
                }
                return response()->json($payload);
            }

            return redirect()->away(
                env('FRONTEND_URL', 'http://127.0.0.1:8001') . '/dashboard?' . http_build_query($payload)
            );

        } catch (\Exception $e) {
            Log::error('Error en Google Callback: ' . $e->getMessage());

            if ((bool) session()->pull('oauth.desktop', false)) {
                return response()->json([
                    'error'  => 'Error al autenticar con Google',
                    'detail' => $e->getMessage(),
                ], 400);
            }

            return redirect()->away(
                env('FRONTEND_URL', 'http://127.0.0.1:8001') . '/login?error=' . urlencode('Error al autenticar con Google: ' . $e->getMessage())
            );
        }
    }

    private function isAllowedReturnUri(?string $uri): bool
    {
        if (!$uri) return false;
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $allowed = array_filter(array_map('trim', explode(',', env('ALLOWED_APP_SCHEMES', 'geiico'))));
        return $scheme && in_array($scheme, $allowed, true);
    }

    public function logout(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'No hay sesión activa'], 401);
        }
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente', 'status' => 'success']);
    }
}