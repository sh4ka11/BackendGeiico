<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;
    protected GoogleDrive $service;
    protected string $folderId;

    public function __construct()
    {
        // Inicializa el cliente de Google
        $this->client = new Client();
        $this->client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $this->client->setAuthConfig(storage_path('app/google/service-account.json'));
        $this->client->addScope(GoogleDrive::DRIVE);
        $this->client->setAccessType('offline');

        // ID de la carpeta de Drive donde subirás archivos
        $folderId = config('services.google.folder_id');
        if (!$folderId) {
            throw new \Exception('No se ha configurado GOOGLE_FOLDER_ID en config/services.php o en el .env');
        }
        $this->folderId = $folderId;

        // Inicializa el servicio de Google Drive
        $this->service = new GoogleDrive($this->client);

        // Si hay un token almacenado, lo carga
        $this->loadAccessToken();
    }

    /**
     * Carga el token de acceso desde el almacenamiento.
     */
    protected function loadAccessToken(): void
    {
        if (session()->has('google_access_token')) {
            $this->client->setAccessToken(session('google_access_token'));

            // Renueva el token si ha expirado
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken();
                if ($refreshToken) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    session(['google_access_token' => $newToken]);
                } else {
                    throw new Exception('El token de Google Drive ha expirado y no hay refresh token disponible.');
                }
            }
        }
    }

    /**
     * Sube un archivo a Google Drive.
     *
     * @param string      $filePath
     * @param string      $fileName
     * @param string      $mimeType
     * @param string|null $parentId   Carpeta destino (si es null usa $this->folderId)
     * @return string               ID del archivo en Drive
     */
    public function uploadFile(string $filePath, string $fileName, string $mimeType, ?string $parentId = null): string
    {
        $targetFolder = $parentId ?: $this->folderId;

        $fileMetadata = new DriveFile([
            'name'    => $fileName,
            'parents' => [$targetFolder],    // <-- usa el parent dinámico
        ]);

        $content = file_get_contents($filePath);

        $file = $this->service->files->create($fileMetadata, [
            'data'       => $content,
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id',
        ]);

        return $file->id;
    }




/**
 * Crear carpeta en Google Drive.
 *
 * @param string $folderName Nombre de la carpeta
 * @param string|null $parentId ID de la carpeta padre (opcional)
 * @return string ID de la carpeta creada
 */
public function createFolder(string $folderName, ?string $parentId = null): string
{
    try {
        // Registrar los valores entrantes para depuración
        Log::info('Creando carpeta con parámetros:', [
            'folderName' => $folderName,
            'parentId' => $parentId ?: 'null'
        ]);
        
        // Manejar null, cadena vacía o "undefined" como ID padre
        if ($parentId === null || $parentId === '' || $parentId === 'undefined') {
            $parent = $this->folderId;
            Log::info('Usando ID de carpeta por defecto:', ['folderId' => $parent]);
        } else {
            $parent = $parentId;
            Log::info('Usando ID de carpeta proporcionado:', ['parentId' => $parent]);
        }
        
        // Verificar que parent sea un ID válido
        if (empty($parent)) {
            throw new \Exception("ID de carpeta padre inválido");
        }
        
        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parent],
        ]);
        
        $folder = $this->service->files->create($fileMetadata, [
            'fields' => 'id'
        ]);
        
        Log::info('Carpeta creada exitosamente:', ['folderId' => $folder->id]);
        return $folder->id;
    } catch (\Exception $e) {
        Log::error('Error al crear carpeta en Google Drive:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}
    /**
     * Listar archivos/carpetas en una carpeta de Drive.
     *
     * @param string|null $parentId ID de la carpeta padre (opcional)
     * @return array Lista de archivos/carpetas
     */
    public function listFiles(?string $parentId = null, ?int $userId = null): array
    {
        try {
            // Si no hay userId, no mostrar nada (seguridad)
            if (!$userId) {
                return [];
            }
            
            // Obtener directamente de la base de datos local, sin consultar Google Drive
            $filesQuery = \App\Models\DriveFile::where('user_id', $userId);
            
            // Filtrar por carpeta padre si se especifica
            if ($parentId) {
                $filesQuery->where('parent_id', $parentId);
            } else {
                // Archivos en la raíz (sin padre o con padre=root)
                $filesQuery->where(function($query) {
                    $query->whereNull('parent_id')
                          ->orWhere('parent_id', 'root');
                });
            }
            
            $fileRecords = $filesQuery->get();
            
            // Crear objetos compatibles con la API de Google Drive
            $formattedFiles = [];
            foreach ($fileRecords as $file) {
                $driveFile = new \stdClass();
                $driveFile->id = $file->drive_file_id;
                $driveFile->name = $file->name;
                $driveFile->mimeType = $file->mime_type;
                $driveFile->createdTime = $file->created_at->format('Y-m-d\TH:i:s.vP');
                $driveFile->modifiedTime = $file->updated_at->format('Y-m-d\TH:i:s.vP');
                $driveFile->parents = [$file->parent_id];
                
                $formattedFiles[] = $driveFile;
            }
            
            return $formattedFiles;
        } catch (\Exception $e) {
            Log::error("Error en GoogleDriveService::listFiles: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Listar solo carpetas en una carpeta de Drive.
     *
     * @param string|null $parentId ID de la carpeta padre (opcional)
     * @return array Lista de carpetas
     */
    public function listFolders(?string $parentId = null, ?int $userId = null): array
    {
        try {
            // Si no hay userId, no mostrar nada (seguridad)
            if (!$userId) {
                return [];
            }
            
            // Obtener directamente de la base de datos local, sin consultar Google Drive
            $foldersQuery = \App\Models\DriveFile::where('user_id', $userId)
                ->where('mime_type', 'application/vnd.google-apps.folder');
            
            // Filtrar por carpeta padre si se especifica
            if ($parentId) {
                $foldersQuery->where('parent_id', $parentId);
            } else {
                // Carpetas en la raíz (sin padre o con padre=root)
                $foldersQuery->where(function($query) {
                    $query->whereNull('parent_id')
                          ->orWhere('parent_id', 'root');
                });
            }
            
            $folderRecords = $foldersQuery->get();
            
            // Crear objetos compatibles con la API de Google Drive
            $formattedFolders = [];
            foreach ($folderRecords as $folder) {
                $driveFolder = new \stdClass();
                $driveFolder->id = $folder->drive_file_id;
                $driveFolder->name = $folder->name;
                $driveFolder->mimeType = $folder->mime_type;
                $driveFolder->createdTime = $folder->created_at->format('Y-m-d\TH:i:s.vP');
                $driveFolder->parentId = $folder->parent_id;
                
                $formattedFolders[] = $driveFolder;
            }
            
            return $formattedFolders;
        } catch (\Exception $e) {
            Log::error("Error en GoogleDriveService::listFolders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Eliminar un archivo de Google Drive (moverlo a la papelera).
     *
     * @param string $fileId El ID del archivo a eliminar
     * @return void
     */
    public function deleteFile(string $fileId): void
    {
        // En lugar de eliminar, movemos a la papelera
        $this->trashFile($fileId);
    }

    /**
     * Genera la URL pública para un archivo en Google Drive
     *
     * @param string $fileId ID del archivo en Google Drive
     * @param bool $isFolder Si es una carpeta o no
     * @return string URL pública
     */
    public function getPublicUrl(string $fileId, bool $isFolder = false): string
    {
        if ($isFolder) {
            return "https://drive.google.com/drive/folders/{$fileId}";
        } else {
            return "https://drive.google.com/file/d/{$fileId}/view";
        }
    }

    /**
     * Genera URL para previsualización de un archivo
     *
     * @param string $fileId ID del archivo
     * @return string URL de previsualización
     */
    public function getPreviewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    /**
     * Genera URL para descargar un archivo
     *
     * @param string $fileId ID del archivo
     * @return string URL de descarga
     */
    public function getDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?id={$fileId}&export=download";
    }

    /**
     * Obtiene información adicional para un archivo, incluyendo URLs
     *
     * @param string $fileId ID del archivo
     * @return array Información del archivo con URLs
     */
    public function getFileWithUrls(string $fileId): array
    {
        try {
            $file = $this->service->files->get($fileId, ['fields' => 'id,name,mimeType,webViewLink,webContentLink']);
            
            $isFolder = $file->getMimeType() === 'application/vnd.google-apps.folder';
            
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'viewUrl' => $isFolder ? $this->getPublicUrl($fileId, true) : $this->getPublicUrl($fileId),
                'downloadUrl' => !$isFolder ? $this->getDownloadUrl($fileId) : null,
                'previewUrl' => !$isFolder ? $this->getPreviewUrl($fileId) : null,
            ];
        } catch (\Exception $e) {
            Log::error("Error obteniendo información del archivo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener información básica sobre un archivo
     *
     * @param string $fileId El ID del archivo
     * @return array|null Información del archivo o null si no se encuentra
     */
    public function getFileInfo(string $fileId): ?array
    {
        try {
            $file = $this->service->files->get($fileId, ['fields' => 'id,name,mimeType,createdTime']);
            
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'createdTime' => $file->getCreatedTime()
            ];
        } catch (\Exception $e) {
            Log::error("Error al obtener información del archivo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mueve un archivo a la papelera en lugar de eliminarlo.
     *
     * @param string $fileId El ID del archivo a mover a la papelera
     * @return void
     */
    public function trashFile(string $fileId): void
    {
        // En Google Drive, mover a la papelera es establecer la propiedad 'trashed' a true
        $file = new \Google\Service\Drive\DriveFile();
        $file->setTrashed(true);
        
        $this->service->files->update($fileId, $file);
        
        Log::info("Archivo movido a la papelera: {$fileId}");
    }

    /**
     * Lista todos los archivos que están en la papelera.
     *
     * @return array Lista de archivos en la papelera
     */
    public function listTrashedFiles(?int $userId = null): array
    {
        try {
            // Si hay userId, obtener IDs de archivos del usuario
            if ($userId) {
                $userFileIds = \App\Models\DriveFileBackup::where('user_id', $userId)
                    ->pluck('drive_file_id')
                    ->toArray();
                
                if (empty($userFileIds)) {
                    return [];
                }
            }
            
            $parameters = [
                'q' => "trashed = true",
                'fields' => 'files(id, name, mimeType, createdTime, parents)'
            ];
            
            $files = $this->service->files->listFiles($parameters);
            $trashedFiles = $files->getFiles();
            
            // Filtrar por usuario si es necesario
            if ($userId && !empty($userFileIds)) {
                $filteredFiles = array_filter($trashedFiles, function($file) use ($userFileIds) {
                    return in_array($file->getId(), $userFileIds);
                });
                
                return $filteredFiles;
            }
            
            return $trashedFiles;
        } catch (\Exception $e) {
            Log::error("Error en GoogleDriveService::listTrashedFiles: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Restaura un archivo desde la papelera.
     *
     * @param string $fileId El ID del archivo a restaurar
     * @return void
     */
    public function restoreFile(string $fileId): void
    {
        $file = new \Google\Service\Drive\DriveFile();
        $file->setTrashed(false);
        
        $this->service->files->update($fileId, $file);
        
        Log::info("Archivo restaurado desde la papelera: {$fileId}");
    }

    /**
     * Elimina permanentemente un archivo (sin posibilidad de recuperación).
     *
     * @param string $fileId El ID del archivo a eliminar permanentemente
     * @return void
     */
    public function permanentlyDeleteFile(string $fileId): void
    {
        $this->service->files->delete($fileId);
        
        Log::info("Archivo eliminado permanentemente: {$fileId}");
    }

    /**
     * Vacía completamente la papelera eliminando todos los archivos de forma permanente.
     *
     * @return int Número de archivos eliminados
     */
    public function emptyTrash(): int
    {
        $trashedFiles = $this->listTrashedFiles();
        $count = 0;
        
        foreach ($trashedFiles as $file) {
            $this->service->files->delete($file->getId());
            $count++;
        }
        
        Log::info("Papelera vaciada: {$count} archivos eliminados permanentemente");
        return $count;
    }
}