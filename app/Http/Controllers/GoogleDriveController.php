<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;

class GoogleDriveController extends Controller
{
    public function upload(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB máximo, sin restricción de tipo
            'parent_id' => 'nullable|string',
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->getPathname();
            $fileName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $parentId = $request->input('parent_id');
            
            Log::info('Subiendo archivo:', [
                'nombre' => $fileName,
                'tipo' => $mimeType,
                'carpeta' => $parentId ?: 'raíz',
                'usuario' => auth()->id()
            ]);
            
            $fileId = $driveService->uploadFile($filePath, $fileName, $mimeType, $parentId);
            
            // Guardar la relación en la base de datos
            \App\Models\DriveFile::create([
                'user_id' => auth()->id(),
                'drive_file_id' => $fileId,
                'name' => $fileName,
                'mime_type' => $mimeType,
                'parent_id' => $parentId
            ]);
            
            return response()->json(['success' => true, 'file_id' => $fileId]);
        } catch (\Exception $e) {
            Log::error('Error al subir archivo: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function createFolder(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|string'
        ]);
        
        $name = $request->input('name');
        $parentId = $request->input('parent_id');
        
        try {
            $folderId = $driveService->createFolder($name, $parentId);
            
            // Asociar carpeta con el usuario actual
            \App\Models\DriveFile::create([
                'user_id' => auth()->id(),
                'drive_file_id' => $folderId,
                'name' => $name,
                'mime_type' => 'application/vnd.google-apps.folder',
                'parent_id' => $parentId
            ]);
            
            return response()->json([
                'success' => true,
                'folder_id' => $folderId, 
                'name' => $name
            ]);
        } catch (\Exception $e) {
            Log::error("Error al crear carpeta: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function listFiles(Request $request, GoogleDriveService $driveService)
    {
        try {
            $user = auth()->user();
            $userId = $user->id;
            $parentId = $request->input('parent_id');
            $viewAll = $request->boolean('view_all', false); // Convertir a booleano
            
            // Añadir log para depuración
            Log::info('listFiles llamado', [
                'user_id' => $userId,
                'parent_id' => $parentId,
                'view_all' => $viewAll,
                'roles' => method_exists($user, 'roles') ? $user->roles->pluck('name')->toArray() : []
            ]);
            
            // Verificar si el usuario puede ver todos los archivos - simplificar la detección
            $isAdmin = method_exists($user, 'roles') ? $user->roles->where('slug', 'admin')->count() > 0 : false;
            $isViewer = method_exists($user, 'roles') ? $user->roles->where('slug', 'view-all-files')->count() > 0 : false;
            $canViewAll = $isAdmin || $isViewer || $viewAll;
            
            Log::info('Permisos de usuario', [
                'isAdmin' => $isAdmin,
                'isViewer' => $isViewer,
                'canViewAll' => $canViewAll
            ]);
            
            // Construir la consulta base
            $query = \App\Models\DriveFile::query();
            
            // Si puede ver todos, no filtramos por usuario
            if (!$canViewAll) {
                $query->where('user_id', $userId);
            }
            
            // Filtrar por carpeta padre
            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id')->orWhere('parent_id', 'root');
            }
            
            // Ejecutar la consulta
            $dbFiles = $query->get();
            
            // Verificar si se encontraron archivos
            Log::info('Archivos encontrados', [
                'count' => $dbFiles->count(),
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // Formatear archivos para la respuesta
            $formattedFiles = [];
            foreach ($dbFiles as $file) {
                // Determinar el ID correcto según el campo disponible
                $fileId = $file->drive_id ?? $file->drive_file_id ?? null;
                
                $formattedFiles[] = [
                    'id' => $fileId,
                    'name' => $file->name,
                    'mimeType' => $file->mime_type,
                    'createdTime' => $file->created_at->format('Y-m-d\TH:i:s.vP'),
                    'modifiedTime' => $file->updated_at->format('Y-m-d\TH:i:s.vP'),
                    'parents' => [$file->parent_id],
                    'userId' => $file->user_id
                ];
            }
            
            return response()->json([
                'success' => true,
                'files' => $formattedFiles,
                'debug_db_files' => $dbFiles,
                'user_id' => $userId,
                'parent_id' => $parentId,
                'can_view_all' => $canViewAll
            ]);
        } catch (\Exception $e) {
            Log::error('Error en listFiles: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar solo las carpetas.
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function listFolders(Request $request, GoogleDriveService $driveService)
    {
        try {
            $user = auth()->user();
            $userId = $user->id;
            $parentId = $request->input('parent_id');
            $viewAll = $request->boolean('view_all', false);
            
            Log::info("Solicitando carpetas", [
                'user_id' => $userId, 
                'parent_id' => $parentId,
                'view_all' => $viewAll
            ]);
            
            // Verificar permisos
            $isAdmin = method_exists($user, 'roles') ? $user->roles->where('slug', 'admin')->count() > 0 : false;
            $isViewer = method_exists($user, 'roles') ? $user->roles->where('slug', 'view-all-files')->count() > 0 : false;
            $canViewAll = $isAdmin || $isViewer || $viewAll;
            
            // Construir la consulta base para carpetas
            $query = \App\Models\DriveFile::where('mime_type', 'application/vnd.google-apps.folder');
            
            // Filtrar por usuario si no puede ver todos
            if (!$canViewAll) {
                $query->where('user_id', $userId);
            }
            
            // Filtrar por carpeta padre
            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id')->orWhere('parent_id', 'root');
            }
            
            // Ejecutar la consulta
            $folders = $query->get();
            
            // Formatear para la respuesta
            $formattedFolders = [];
            foreach ($folders as $folder) {
                $folderId = $folder->drive_id ?? $folder->drive_file_id ?? null;
                
                $formattedFolders[] = [
                    'id' => $folderId,
                    'name' => $folder->name,
                    'mimeType' => $folder->mime_type,
                    'createdTime' => $folder->created_at->format('Y-m-d\TH:i:s.vP'),
                    'parentId' => $folder->parent_id,
                    'userId' => $folder->user_id
                ];
            }
            
            return response()->json([
                'success' => true,
                'folders' => $formattedFolders,
                'count' => $folders->count(),
                'can_view_all' => $canViewAll
            ]);
        } catch (\Exception $e) {
            Log::error("Error al listar carpetas: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un archivo o carpeta de Google Drive.
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string'
        ]);
        
        $fileId = $request->input('file_id');
        
        try {
            $driveService->deleteFile($fileId);
            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mover un archivo a la papelera.
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string'
        ]);
        
        $fileId = $request->input('file_id');
        $userId = auth()->id();
        
        try {
            // 1. Buscar el archivo en la base de datos
            $driveFile = \App\Models\DriveFile::where('drive_file_id', $fileId)
                ->where('user_id', $userId)
                ->first();
        
            if (!$driveFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Archivo no encontrado o no tienes permiso para moverlo a la papelera'
                ], 404);
            }
        
            // 2. Crear un respaldo del archivo en la tabla de papelera
            \App\Models\DriveFileBackup::create([
                'user_id' => $driveFile->user_id,
                'drive_file_id' => $driveFile->drive_file_id,
                'name' => $driveFile->name,
                'mime_type' => $driveFile->mime_type,
                'parent_id' => $driveFile->parent_id,
                'deleted_at' => now()
            ]);
        
            // 3. Eliminar de la tabla principal
            $driveFile->delete();
        
            // 4. Mover a papelera en Google Drive
            $driveService->trashFile($fileId);
        
            // 5. Registrar la acción
            Log::info("Usuario {$userId} movió archivo {$fileId} a la papelera");
        
            return response()->json([
                'success' => true, 
                'message' => 'Archivo movido a la papelera correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error("Error al mover archivo a papelera: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar archivos en la papelera.
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function listTrash(Request $request, GoogleDriveService $driveService)
    {
        try {
            $userId = auth()->id();
            
            // Consultar la tabla de respaldos en lugar de Google Drive directamente
            $trashedFiles = \App\Models\DriveFileBackup::where('user_id', $userId)
                ->orderBy('deleted_at', 'desc')
                ->get();
            
            // Formatear para la respuesta
            $formattedFiles = [];
            foreach ($trashedFiles as $file) {
                $formattedFiles[] = [
                    'id' => $file->drive_file_id,
                    'name' => $file->name,
                    'mimeType' => $file->mime_type,
                    'createdTime' => $file->created_at->format('Y-m-d\TH:i:s.vP'),
                    'trashedTime' => $file->deleted_at->format('Y-m-d\TH:i:s.vP'),
                    'parentId' => $file->parent_id,
                ];
            }
            
            return response()->json(['success' => true, 'files' => $formattedFiles]);
        } catch (\Exception $e) {
            Log::error("Error al listar archivos en papelera: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restaurar un archivo desde la papelera.
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string'
        ]);
        
        $fileId = $request->input('file_id');
        $userId = auth()->id();
        
        try {
            // 1. Buscar en la tabla de respaldos
            $backupFile = \App\Models\DriveFileBackup::where('drive_file_id', $fileId)
                ->where('user_id', $userId)
                ->first();
        
            if (!$backupFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Archivo no encontrado en la papelera'
                ], 404);
            }
        
            // 2. Restaurar en la tabla principal
            \App\Models\DriveFile::create([
                'user_id' => $backupFile->user_id,
                'drive_file_id' => $backupFile->drive_file_id,
                'name' => $backupFile->name,
                'mime_type' => $backupFile->mime_type,
                'parent_id' => $backupFile->parent_id
            ]);
        
            // 3. Eliminar de la tabla de respaldos
            $backupFile->delete();
        
            // 4. Restaurar en Google Drive
            $driveService->restoreFile($fileId);
        
            return response()->json([
                'success' => true,
                'message' => 'Archivo restaurado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina permanentemente un archivo (sin posibilidad de recuperación).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function permanentlyDeleteFile(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string'
        ]);
        
        $fileId = $request->input('file_id');
        $userId = auth()->id();
        
        try {
            Log::info("Iniciando eliminación permanente", [
                'file_id' => $fileId,
                'user_id' => $userId
            ]);
            
            // Lo más importante: Eliminar el archivo de la tabla de respaldos
            $backup = \App\Models\DriveFileBackup::where('user_id', $userId)
                ->where('drive_file_id', $fileId)
                ->first();
                
            if ($backup) {
                Log::info("Eliminando registro de la papelera local", ['backup_id' => $backup->id]);
                $backup->delete();
                
                // Intentar eliminar de Google Drive (si existe)
                try {
                    $driveService = app(GoogleDriveService::class);
                    $driveService->permanentlyDeleteFile($fileId);
                    Log::info("Archivo eliminado de Google Drive");
                } catch (\Exception $e) {
                    // Si falla al eliminar de Google Drive, no es crítico
                    // El usuario eliminó el archivo de la papelera local, que es lo importante
                    Log::warning("No se pudo eliminar de Google Drive, pero se eliminó de la papelera local", [
                        'error' => $e->getMessage()
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo eliminado permanentemente de la papelera'
                ]);
            } else {
                // No encontramos el archivo en la papelera local
                Log::warning("Archivo no encontrado en la papelera local", ['file_id' => $fileId]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Archivo no encontrado en la papelera'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error al eliminar permanentemente: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vaciar completamente la papelera.
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function emptyTrash(Request $request, GoogleDriveService $driveService)
    {
        try {
            $userId = auth()->id();
            
            // 1. Obtener todos los archivos de la papelera (tabla DriveFileBackup)
            $backupFiles = \App\Models\DriveFileBackup::where('user_id', $userId)->get();
            $count = 0;
            
            // 2. Recorrer cada archivo y eliminarlo
            foreach ($backupFiles as $backupFile) {
                $fileId = $backupFile->drive_file_id;
                
                // Primero borrar el registro local
                $backupFile->delete();
                $count++;
                
                // Intentar eliminar de Google Drive (pero no fallar si no se encuentra)
                try {
                    $driveService->permanentlyDeleteFile($fileId);
                } catch (\Exception $e) {
                    // Registrar el error pero continuar con los demás archivos
                    \Illuminate\Support\Facades\Log::warning("Error al eliminar archivo de Google Drive durante emptyTrash: {$e->getMessage()}", [
                        'file_id' => $fileId
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "{$count} archivos eliminados permanentemente",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al vaciar papelera: {$e->getMessage()}");
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprobar si existe un archivo con el mismo nombre en Google Drive
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkFileExistsInDrive(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|string'
        ]);
        
        try {
            $fileName = $request->input('name');
            $parentId = $request->input('parent_id');
            
            Log::info("Comprobando si el archivo existe en Drive", [
                'file_name' => $fileName,
                'parent_id' => $parentId
            ]);
            
            $exists = $driveService->checkFileExists($parentId, $fileName);
            
            return response()->json([
                'success' => true,
                'exists' => $exists
            ]);
        } catch (\Exception $e) {
            Log::error("Error al comprobar si el archivo existe en Drive: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprobar si existe una carpeta con el mismo nombre en Google Drive
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkFolderExistsInDrive(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|string'
        ]);
        
        try {
            $folderName = $request->input('name');
            $parentId = $request->input('parent_id');
            
            Log::info("Comprobando si la carpeta existe en Drive", [
                'folder_name' => $folderName,
                'parent_id' => $parentId
            ]);
            
            $exists = $driveService->checkFolderExists($parentId, $folderName);
            
            return response()->json([
                'success' => true,
                'exists' => $exists
            ]);
        } catch (\Exception $e) {
            Log::error("Error al comprobar si la carpeta existe en Drive: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renombrar un archivo o carpeta
     * 
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function renameFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string',
            'new_name' => 'required|string|max:255'
        ]);
        
        $fileId = $request->input('file_id');
        $newName = $request->input('new_name');
        $userId = auth()->id();
        
        try {
            // 1. Verificar que el archivo pertenezca al usuario
            $driveFile = \App\Models\DriveFile::where('drive_file_id', $fileId)
                ->where('user_id', $userId)
                ->first();
                
            if (!$driveFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Archivo no encontrado o no tienes permiso para renombrarlo'
                ], 403);
            }
            
            // 2. Renombrar en Google Drive
            $renamed = $driveService->renameFile($fileId, $newName);
            
            if (!$renamed) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo renombrar el archivo en Google Drive'
                ], 500);
            }
            
            // 3. Actualizar en la base de datos local
            $driveFile->name = $newName;
            $driveFile->save();
            
            // 4. Registrar la acción
            Log::info("Usuario {$userId} renombró {$fileId} de '{$driveFile->getOriginal('name')}' a '{$newName}'");
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo renombrado correctamente',
                'new_name' => $newName
            ]);
        } catch (\Exception $e) {
            Log::error("Error al renombrar archivo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mover un archivo o carpeta a una nueva ubicación
     * 
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string',
            // permitir null o 'root' como destino (raíz)
            'destination_folder_id' => 'nullable|string'
        ]);

        $fileId = $request->input('file_id');
        $destinationFolderId = $request->input('destination_folder_id'); // puede ser null | 'root' | folderId
        $userId = auth()->id();

        try {
            $driveFile = \App\Models\DriveFile::where('drive_file_id', $fileId)
                ->where('user_id', $userId)
                ->first();

            if (!$driveFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Archivo no encontrado o no tienes permiso para moverlo'
                ], 403);
            }

            $toRoot = ($destinationFolderId === null || $destinationFolderId === '' || $destinationFolderId === 'root');

            // Si destino es raíz y el archivo YA está en raíz, 422
            if ($toRoot && (empty($driveFile->parent_id) || $driveFile->parent_id === 'root')) {
                return response()->json([
                    'success' => false,
                    'error' => 'El elemento ya está en la carpeta raíz'
                ], 422);
            }

            // Verificar carpeta destino cuando NO es raíz
            if (!$toRoot) {
                $destinationFolder = \App\Models\DriveFile::where('drive_file_id', $destinationFolderId)
                    ->where('user_id', $userId)
                    ->where('mime_type', 'application/vnd.google-apps.folder')
                    ->first();

                if (!$destinationFolder) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Carpeta de destino no encontrada o no tienes permiso para usarla'
                    ], 403);
                }
            }

            // Mover en Google Drive (usar 'root' si va a raíz)
            $moved = $driveService->moveFile($fileId, $toRoot ? 'root' : $destinationFolderId);
            if (!$moved) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo mover el archivo en Google Drive'
                ], 500);
            }

            // Actualizar BD local (null para raíz)
            $driveFile->parent_id = $toRoot ? null : $destinationFolderId;
            $driveFile->save();

            Log::info("Usuario {$userId} movió {$fileId} a " . ($toRoot ? 'root' : $destinationFolderId));

            return response()->json([
                'success' => true,
                'message' => 'Elemento movido correctamente',
                'file_id' => $fileId,
                'destination_folder_id' => $toRoot ? 'root' : $destinationFolderId
            ]);
        } catch (\Exception $e) {
            Log::error("Error al mover archivo: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Copiar un archivo a una nueva ubicación
     * 
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function copyFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string',
            // permitir null o 'root' como destino (raíz)
            'destination_folder_id' => 'nullable|string'
        ]);

        $fileId = $request->input('file_id');
        $destinationFolderId = $request->input('destination_folder_id');
        $userId = auth()->id();

        try {
            $driveFile = \App\Models\DriveFile::where('drive_file_id', $fileId)
                ->where('user_id', $userId)
                ->first();

            if (!$driveFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Archivo no encontrado o no tienes permiso para copiarlo'
                ], 403);
            }

            // No copiar carpetas (evita 500 de Google)
            if ($driveFile->mime_type === 'application/vnd.google-apps.folder') {
                return response()->json([
                    'success' => false,
                    'error' => 'Copiar carpetas no está soportado actualmente'
                ], 422);
            }

            $toRoot = ($destinationFolderId === null || $destinationFolderId === '' || $destinationFolderId === 'root');

            if (!$toRoot) {
                $destinationFolder = \App\Models\DriveFile::where('drive_file_id', $destinationFolderId)
                    ->where('user_id', $userId)
                    ->where('mime_type', 'application/vnd.google-apps.folder')
                    ->first();

                if (!$destinationFolder) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Carpeta de destino no encontrada o no tienes permiso para usarla'
                    ], 403);
                }
            }

            $newFileId = $driveService->copyFile($fileId, $toRoot ? 'root' : $destinationFolderId);
            if (!$newFileId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo copiar el archivo en Google Drive'
                ], 500);
            }

            \App\Models\DriveFile::create([
                'user_id'       => $userId,
                'drive_file_id' => $newFileId,
                'name'          => $driveFile->name,
                'mime_type'     => $driveFile->mime_type,
                'parent_id'     => $toRoot ? null : $destinationFolderId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo copiado correctamente',
                'original_file_id' => $fileId,
                'new_file_id' => $newFileId,
                'destination_folder_id' => $toRoot ? 'root' : $destinationFolderId
            ]);
        } catch (\Exception $e) {
            Log::error("Error al copiar archivo: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}