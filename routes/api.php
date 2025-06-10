<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Console\Input\Input;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleDriveController;

use App\Http\Controllers\Auth\GoogleController;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
 
    return ['token' => $token->plainTextToken];
});

Route::get('/login-google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/google-callback', [GoogleController::class, 'handleGoogleCallback']);

// Rutas que requieren autenticación
Route::middleware('auth:sanctum')->group(function() {
    // Subida y gestión de archivos
    Route::post('/upload-to-drive', [GoogleDriveController::class, 'upload']);
    Route::post('/create-folder', [GoogleDriveController::class, 'createFolder']);
    Route::get('/list-files', [GoogleDriveController::class, 'listFiles']);
    Route::get('/list-folders', [GoogleDriveController::class, 'listFolders'])->name('drive.folders');
    
    // Operaciones que requieren verificación de propiedad
    Route::middleware('check.file.owner')->group(function() {
        Route::delete('/delete-file', [GoogleDriveController::class, 'deleteFile']);
        Route::post('/trash-file', [GoogleDriveController::class, 'trashFile']);
        Route::post('/restore-file', [GoogleDriveController::class, 'restoreFile']);
        Route::delete('/permanently-delete-file', [GoogleDriveController::class, 'permanentlyDeleteFile']);
    });
    
    // Papelera/backup
    Route::get('/trash', [GoogleDriveController::class, 'listTrash']);
    Route::get('/list-trash', [GoogleDriveController::class, 'listTrash']);
    Route::delete('/empty-trash', [GoogleDriveController::class, 'emptyTrash']);
    
    // Autenticación
    Route::post('/logout', [GoogleController::class, 'logout']);
});

// Route::post('/login', function (Request $request) {
//     $user = User::where('email', $request->input('email'))->first();

//     if (!$user || !Hash::check($request->password, $user->password)) {
//         return response()->json([
//             'message' => 'Credenciales incorrectas',
//         ], 401);
//     }
//     return response()->json([
//         'user'=> [
//             'name' => $user->name,
//             'email' => $user->email,
//         ],

//         'token' => $user->createToken('api')->plainTextToken,   
//     ]);

// })->name('login');

// Route::post('/register', [AuthController::class, 'register'])->name('register');
// Route::middleware('auth:sanctum')->post('/logout', [GoogleController::class, 'logout']);


// Si no va a utilizar las migraciones predeterminadas de Sanctum

// php artisan vendor:publish --tag=sanctum-migrations



// Route::get('/login-google', [GoogleController::class, 'redirectToGoogle']);
// Route::get('/google-callback', [GoogleController::class, 'handleGoogleCallback']);

//
// Route::middleware(['auth:sanctum', 'ability:create-post'])->get('/post/create', function (Request $request){
// return [
//     'id' => 1,
//     'title' => $request->title,
//     'content' => $request->content,
// ]; 
// });

// Ruta para pruebas - obtener token directamente con credenciales de Google
Route::post('/get-token-for-testing', function (Request $request) {
    // SOLO PARA ENTORNO DE DESARROLLO
    if (app()->environment('production')) {
        return response()->json(['error' => 'No disponible en producción'], 403);
    }
    
    $email = $request->input('email');
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }
    
    return response()->json([
        'token' => $user->createToken('test-token')->plainTextToken
    ]);
});

// Añadir temporalmente a tu archivo de rutas api.php
Route::middleware('auth:sanctum')->get('/check-user', function (Request $request) {
    return [
        'user_id' => auth()->id(),
        'email' => auth()->user()->email,
        'timestamp' => now()->toIso8601String()
    ];
});
