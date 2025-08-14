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
use App\Http\Controllers\LaboratoryConfigController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\CalibrationReportController;
use App\Http\Controllers\UserDefaultValueController;
use App\Http\Controllers\Admin\DatabaseAdminController;
use App\Http\Controllers\Admin\CalibrationReportAdminController;
use App\Http\Controllers\Admin\DriveFileAdminController;
use App\Http\Controllers\Admin\DriveFileBackupAdminController;
use App\Http\Controllers\Admin\LaboratoryConfigAdminController;
use App\Http\Controllers\Admin\EquipmentAdminController;
use App\Http\Controllers\Admin\ClientAdminController;

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
    $user = $request->user();
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $user->avatar,
        'roles' => $user->roles->pluck('slug')->toArray(),
        'is_admin' => $user->isAdmin(),
        'permissions' => $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug');
        })->unique()->values()->toArray()
    ]);
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
    
    // Comprobar si un archivo existe en Drive
    Route::post('/check-file-exists-in-drive', [GoogleDriveController::class, 'checkFileExistsInDrive']);
    // Comprobar si una carpeta existe en Drive
    Route::post('/check-folder-exists-in-drive', [GoogleDriveController::class, 'checkFolderExistsInDrive']);
    
    // Operaciones que requieren verificación de propiedad
    Route::middleware('check.file.owner')->group(function() {
        Route::delete('/delete-file', [GoogleDriveController::class, 'deleteFile']);
        Route::post('/trash-file', [GoogleDriveController::class, 'trashFile']);
        Route::post('/restore-file', [GoogleDriveController::class, 'restoreFile']);
    });
    
    // Papelera/backup
    Route::get('/trash', [GoogleDriveController::class, 'listTrash']);
    Route::get('/list-trash', [GoogleDriveController::class, 'listTrash']);
    Route::delete('/empty-trash', [GoogleDriveController::class, 'emptyTrash']);
    
    // Autenticación
    Route::post('/logout', [GoogleController::class, 'logout']);
    Route::delete('/permanently-delete-file', [GoogleDriveController::class, 'permanentlyDeleteFile']);
    
    // Configuración del laboratorio
    Route::get('/laboratory-config', [LaboratoryConfigController::class, 'index']);
    Route::post('/laboratory-config', [LaboratoryConfigController::class, 'store']);
    Route::put('/laboratory-config/{laboratoryConfig}', [LaboratoryConfigController::class, 'update']);
    Route::delete('/laboratory-config/{laboratoryConfig}', [LaboratoryConfigController::class, 'destroy']);
    
    // Clientes
    Route::apiResource('clients', ClientController::class);
    
    // Equipos
    Route::apiResource('equipments', EquipmentController::class);
    
    // Informes de calibración
    Route::get('/calibration-reports', [CalibrationReportController::class, 'index']);
    Route::get('/calibration-reports/{calibrationReport}', [CalibrationReportController::class, 'show']);
    Route::post('/calibration-reports/init', [CalibrationReportController::class, 'initNewReport']);
    Route::post('/calibration-reports', [CalibrationReportController::class, 'store']);
    Route::put('/calibration-reports/{calibrationReport}', [CalibrationReportController::class, 'update']);
    Route::delete('/calibration-reports/{calibrationReport}', [CalibrationReportController::class, 'destroy']);
    Route::get('/calibration-reports/{calibrationReport}/pdf', [CalibrationReportController::class, 'generatePdf']);
    
    // Valores predeterminados del usuario
    Route::get('/user-defaults', [UserDefaultValueController::class, 'index']);
    Route::post('/user-defaults', [UserDefaultValueController::class, 'store']);
    Route::post('/rename-file', [GoogleDriveController::class, 'renameFile']);
    Route::post('/move-file', [GoogleDriveController::class, 'moveFile']);
    Route::post('/copy-file', [GoogleDriveController::class, 'copyFile']);
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
// Route::post('/get-token-for-testing', function (Request $request) {
//     // SOLO PARA ENTORNO DE DESARROLLO
//     if (app()->environment('production')) {
//         return response()->json(['error' => 'No disponible en producción'], 403);
//     }
    
//     $email = $request->input('email');
//     $user = User::where('email', $email)->first();
    
//     if (!$user) {
//         return response()->json(['error' => 'Usuario no encontrado'], 404);
//     }
    
//     return response()->json([
//         'token' => $user->createToken('test-token')->plainTextToken
//     ]);
// });

// Añadir temporalmente a tu archivo de rutas api.php
Route::middleware('auth:sanctum')->get('/check-user', function (Request $request) {
    return [
        'user_id' => auth()->id(),
        'email' => auth()->user()->email,
        'timestamp' => now()->toIso8601String()
    ];
});
Route::middleware('auth:sanctum')->group(function () {
    // Rutas para clientes
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']); // Esta es la que falta
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
});

// Rutas para administración (requieren autenticación y rol admin)
Route::middleware(['auth:sanctum', 'check.role:admin'])->prefix('admin')->group(function () {
    // DB Admin (ya existente) ...
    Route::prefix('database')->group(function () {
        Route::get('/tables', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'index']);
        Route::get('/table/{table}/structure', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'getTableStructure']);
        Route::get('/table/{table}/data', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'getTableData']);
        Route::put('/table/{table}/records/{id}', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'updateRecord']);
        Route::delete('/table/{table}/records/{id}', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'deleteRecord']);
        Route::post('/table/{table}/optimize', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'optimizeTable']);
        Route::post('/maintenance', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'runMaintenance']);
        Route::get('/stats', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'getSystemStats']);
        Route::post('/execute-query', [App\Http\Controllers\Admin\DatabaseAdminController::class, 'executeQuery']);
    });

    // Admin: calibration_reports (ya usas esta sección)
    Route::prefix('calibration-reports')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'store']);
        Route::get('/form-data', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'getFormData']);
        Route::get('/{id}', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'destroy']);
        Route::get('/{id}/export', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'generateExport']);
    });

    // Admin: endpoints planos (sin prefijo admin dentro de admin)
    Route::get('/calibration-reports', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'index']);
    Route::delete('/calibration-reports/{id}', [App\Http\Controllers\Admin\CalibrationReportAdminController::class, 'destroy']);

    // drive_files para la vista BD
    Route::get('/drive-files', [App\Http\Controllers\Admin\DriveFileAdminController::class, 'index']);
    Route::delete('/drive-files/{id}', [App\Http\Controllers\Admin\DriveFileAdminController::class, 'destroy']);
    
    // drive_file_backups
    Route::get('/drive-file-backups', [DriveFileBackupAdminController::class, 'index']);
    Route::post('/drive-file-backups/{id}/restore', [DriveFileBackupAdminController::class, 'restore']);
    Route::delete('/drive-file-backups/{id}', [DriveFileBackupAdminController::class, 'destroy']);
    
    // Configuración del laboratorio
    Route::get('/laboratory-configs', [LaboratoryConfigAdminController::class, 'index']);
    Route::get('/laboratory-configs/{id}', [LaboratoryConfigAdminController::class, 'show']);
    Route::put('/laboratory-configs/{id}', [LaboratoryConfigAdminController::class, 'update']);
    Route::delete('/laboratory-configs/{id}', [LaboratoryConfigAdminController::class, 'destroy']);
    
    // Admin: equipments (global)
    Route::get('/equipments', [EquipmentAdminController::class, 'index']);
    Route::get('/equipments/{id}', [EquipmentAdminController::class, 'show']);
    Route::put('/equipments/{id}', [EquipmentAdminController::class, 'update']);
    Route::delete('/equipments/{id}', [EquipmentAdminController::class, 'destroy']);
    
    // Clients admin (usado por db-clients.js)
    Route::get('/clients', [App\Http\Controllers\Admin\ClientAdminController::class, 'index']);
    Route::get('/clients/{id}', [App\Http\Controllers\Admin\ClientAdminController::class, 'show']);
    Route::put('/clients/{id}', [App\Http\Controllers\Admin\ClientAdminController::class, 'update']);
    Route::delete('/clients/{id}', [App\Http\Controllers\Admin\ClientAdminController::class, 'destroy']); // si ya existe, deja solo una

    // Users admin
    Route::get('/users', [App\Http\Controllers\Admin\UserAdminController::class, 'index']);
    Route::get('/users/{id}', [App\Http\Controllers\Admin\UserAdminController::class, 'show']);
    Route::put('/users/{id}', [App\Http\Controllers\Admin\UserAdminController::class, 'update']);
    Route::delete('/users/{id}', [App\Http\Controllers\Admin\UserAdminController::class, 'destroy']);

    // Asignar/remover roles a un usuario
    Route::put('/users/{userId}/roles', [App\Http\Controllers\Admin\UserRoleController::class, 'updateUserRoles']);
});
