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
            $parentId = $request->input('parent_id');
            $userId = auth()->id();
            
            // Verificación adicional si hay un parent_id
            if ($parentId && $parentId !== 'root') {
                $folder = \App\Models\DriveFile::where('drive_file_id', $parentId)
                    ->where('user_id', $userId)
                    ->first();
                    
                if (!$folder) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No tienes permiso para acceder a esta carpeta'
                    ], 403);
                }
            }
            
            Log::info("Solicitando archivos para usuario: $userId, carpeta: $parentId");
            
            // IMPORTANTE: Consultar directamente la base de datos 
            $dbFiles = \App\Models\DriveFile::where('user_id', $userId)
                ->when($parentId, function($q) use ($parentId) {
                    return $q->where('parent_id', $parentId);
                }, function($q) {
                    return $q->whereNull('parent_id')
                          ->orWhere('parent_id', 'root');
                })
                ->get();
            
            // NUEVA SECCIÓN: Formatear archivos correctamente para la API
            $formattedFiles = [];
            foreach ($dbFiles as $file) {
                $formattedFiles[] = [
                    'id' => $file->drive_file_id,
                    'name' => $file->name,
                    'mimeType' => $file->mime_type,
                    'createdTime' => $file->created_at->format('Y-m-d\TH:i:s.vP'),
                    'modifiedTime' => $file->updated_at->format('Y-m-d\TH:i:s.vP'),
                    'parents' => [$file->parent_id]
                ];
            }
            
            return response()->json([
                'success' => true,
                'files' => $formattedFiles,
                'debug_db_files' => $dbFiles,
                'user_id' => $userId,
                'parent_id' => $parentId
            ]);
        } catch (\Exception $e) {
            Log::error('Error en listFiles: ' . $e->getMessage());
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
            $parentId = $request->input('parent_id');
            $userId = auth()->id();
            
            Log::info("Solicitando carpetas para usuario: $userId, carpeta: $parentId");
            
            // Usar el servicio actualizado
            $folders = $driveService->listFolders($parentId, $userId);
            
            // No es necesario formatear porque el servicio ya devuelve objetos formateados
            return response()->json(['success' => true, 'folders' => $folders]);
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
        
        try {
            $driveService->trashFile($fileId);
            return response()->json([
                'success' => true,
                'message' => 'Archivo movido a la papelera correctamente'
            ]);
        } catch (\Exception $e) {
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
            
            // Usar el servicio actualizado
            $trashedFiles = $driveService->listTrashedFiles($userId);
            
            // Formatear para la respuesta
            $formattedFiles = [];
            foreach ($trashedFiles as $file) {
                $formattedFiles[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType(),
                    'createdTime' => $file->getCreatedTime(),
                    'trashedTime' => now()->toIso8601String(),
                    'parentId' => $file->getParents() ? $file->getParents()[0] : null,
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
        
        try {
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
     * Eliminar permanentemente un archivo (sin posibilidad de recuperación).
     *
     * @param Request $request
     * @param GoogleDriveService $driveService
     * @return \Illuminate\Http\JsonResponse
     */
    public function permanentlyDeleteFile(Request $request, GoogleDriveService $driveService)
    {
        $request->validate([
            'file_id' => 'required|string'
        ]);
        
        $fileId = $request->input('file_id');
        
        try {
            $driveService->permanentlyDeleteFile($fileId);
            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado permanentemente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
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
            // Obtener IDs de archivos que pertenecen al usuario actual
            $userFileIds = \App\Models\DriveFile::where('user_id', auth()->id())
                ->pluck('drive_file_id')
                ->toArray();
            
            // Obtener todos los archivos en papelera
            $allTrashedFiles = $driveService->listTrashedFiles();
            
            // Filtrar para incluir solo archivos del usuario actual
            $count = 0;
            foreach ($allTrashedFiles as $file) {
                if (in_array($file->getId(), $userFileIds)) {
                    $driveService->permanentlyDeleteFile($file->getId());
                    $count++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "{$count} archivos eliminados permanentemente",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}