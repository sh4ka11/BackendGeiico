<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveFile;
use App\Models\DriveFileBackup;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriveFileBackupAdminController extends Controller
{
    // GET /api/admin/drive-file-backups
    public function index(Request $request)
    {
        $perPage = (int)$request->input('per_page', 25);
        $search  = trim((string)$request->input('search', ''));

        $q = DriveFileBackup::query() // <- sin filtro por user_id
            ->orderByDesc('deleted_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('mime_type', 'like', "%{$search}%")
                   ->orWhere('drive_file_id', 'like', "%{$search}%")
                   ->orWhere('parent_id', 'like', "%{$search}%");
            });
        }

        return response()->json(['success' => true, 'data' => $q->paginate($perPage)]);
    }

    // POST /api/admin/drive-file-backups/{id}/restore
    public function restore($id, GoogleDriveService $drive)
    {
        $backup = DriveFileBackup::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // crear registro en tabla principal
        DriveFile::create([
            'user_id'       => $backup->user_id,
            'drive_file_id' => $backup->drive_file_id,
            'name'          => $backup->name,
            'mime_type'     => $backup->mime_type,
            'parent_id'     => $backup->parent_id,
        ]);

        // intentar restaurar en Drive (si falla no rompe)
        try { $drive->restoreFile($backup->drive_file_id); } catch (\Throwable $e) {}

        $backup->delete();

        return response()->json(['success' => true]);
    }

    // DELETE /api/admin/drive-file-backups/{id}
    public function destroy($id, GoogleDriveService $drive)
    {
        $backup = DriveFileBackup::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $fileId = $backup->drive_file_id;
        $backup->delete();

        try { $drive->permanentlyDeleteFile($fileId); } catch (\Throwable $e) {}

        return response()->json(['success' => true]);
    }
}