<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DriveFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckFileOwnership
{
    /**
     * Manejar una solicitud entrante.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo verificar propiedad si hay un file_id o parent_id en la solicitud
        $fileId = $request->input('file_id');
        $parentId = $request->input('parent_id');
        
        if ($fileId) {
            $file = DriveFile::where('drive_file_id', $fileId)->first();
            if ($file && $file->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para acceder a este archivo'
                ], 403);
            }
        }
        
        if ($parentId && $parentId !== 'root' && !is_null($parentId)) {
            $folder = DriveFile::where('drive_file_id', $parentId)->first();
            if ($folder && $folder->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para acceder a esta carpeta'
                ], 403);
            }
        }
        
        return $next($request);
    }
}
