<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaboratoryConfig;
use Illuminate\Http\Request;

class LaboratoryConfigAdminController extends Controller
{
    // GET /api/admin/laboratory-configs
    public function index(Request $request)
    {
        $perPage = (int)$request->input('per_page', 25);
        $search  = trim((string)$request->input('search', ''));

        // Ignora el scope por usuario y muestra todos (pero sin incluir eliminados)
        $q = LaboratoryConfig::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('lab_name', 'like', "%{$search}%")
                   ->orWhere('onac_number', 'like', "%{$search}%")
                   ->orWhere('document_title', 'like', "%{$search}%");
            });
        }

        return response()->json(['success' => true, 'data' => $q->paginate($perPage)]);
    }

    // GET /api/admin/laboratory-configs/{id}
    public function show($id)
    {
        $cfg = LaboratoryConfig::withoutGlobalScopes()->findOrFail($id);
        return response()->json(['success' => true, 'data' => $cfg]);
    }

    // PUT /api/admin/laboratory-configs/{id}
    public function update(Request $request, $id)
    {
        $request->validate([
            'lab_name' => 'required|string|max:255',
            'onac_number' => 'required|string|max:255',
            'document_title' => 'required|string|max:255',
        ]);

        $cfg = LaboratoryConfig::withoutGlobalScopes()->findOrFail($id);
        $cfg->update($request->only(['lab_name','onac_number','document_title']));

        return response()->json(['success' => true, 'data' => $cfg]);
    }

    // DELETE /api/admin/laboratory-configs/{id}
    public function destroy(Request $request, $id)
    {
        $cfg = LaboratoryConfig::withoutGlobalScopes()->findOrFail($id);

        if ($request->boolean('force') || !in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($cfg))) {
            $cfg->forceDelete(); // borrado físico
        } else {
            $cfg->delete(); // soft delete
        }

        return response()->json(['success' => true]);
    }
}