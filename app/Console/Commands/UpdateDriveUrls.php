<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriveFile;
use App\Services\GoogleDriveService;

class UpdateDriveUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drive:update-urls {--user=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las URLs de los archivos de Google Drive';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveService $driveService)
    {
        $userId = $this->option('user');
        
        $query = DriveFile::when($userId, function($query) use ($userId) {
            return $query->where('user_id', $userId);
        })
        ->whereNull('view_url')
        ->orWhereNull('download_url')
        ->orWhereNull('preview_url');
        
        $count = $query->count();
        $this->info("Actualizando $count archivos...");
        
        $bar = $this->output->createProgressBar($count);
        
        $query->chunk(100, function($files) use ($driveService, $bar) {
            foreach ($files as $file) {
                $isFolder = $file->mime_type === 'application/vnd.google-apps.folder';
                
                $file->view_url = $isFolder 
                    ? $driveService->getPublicUrl($file->drive_file_id, true)
                    : $driveService->getPublicUrl($file->drive_file_id);
                    
                if (!$isFolder) {
                    $file->download_url = $driveService->getDownloadUrl($file->drive_file_id);
                    $file->preview_url = $driveService->getPreviewUrl($file->drive_file_id);
                }
                
                $file->save();
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        $this->info("URLs actualizadas correctamente.");
        
        return 0;
    }
}
