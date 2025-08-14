<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveFile;
use App\Models\DriveFileBackup;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriveFileAdminController extends Controller
{
    // GET /api/admin/drive-files
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $search  = trim((string) $request->input('search', ''));

        $q = DriveFile::query()->orderByDesc('id'); // <- sin filtro por user_id

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('mime_type', 'like', "%{$search}%")
                   ->orWhere('parent_id', 'like', "%{$search}%");
            });
        }

        $data = $q->paginate($perPage);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // DELETE /api/admin/drive-files/{id}
    // Mueve a papelera de Google Drive y registra respaldo local
    public function destroy($id, GoogleDriveService $driveService)
    {
        $file = DriveFile::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // 1) Backup en tabla de papelera local
        DriveFileBackup::create([
            'user_id'       => $file->user_id,
            'drive_file_id' => $file->drive_file_id,
            'name'          => $file->name,
            'mime_type'     => $file->mime_type,
            'parent_id'     => $file->parent_id,
            'deleted_at'    => now(),
        ]);

        // 2) Eliminar del listado principal
        $fileId = $file->drive_file_id;
        $file->delete();

        // 3) Mover a papelera en Google Drive (si falla, no rompe el flujo)
        try { $driveService->trashFile($fileId); } catch (\Throwable $e) {}

        return response()->json(['success' => true]);
    }
}