<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentAdminController extends Controller
{
    // GET /api/admin/equipments
    public function index(Request $request)
    {
        $perPage = (int)$request->input('per_page', 25);
        $search  = trim((string)$request->input('search', ''));
        $clientId = $request->input('client_id');
        $userId   = $request->input('user_id');

        $q = Equipment::withoutGlobalScopes()
            ->with('client')
            ->orderByDesc('id');

        if ($clientId) $q->where('client_id', $clientId);
        if ($userId)   $q->where('user_id', $userId);

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('equipment_type', 'like', "%{$search}%")
                   ->orWhere('brand_model', 'like', "%{$search}%")
                   ->orWhere('serial_number', 'like', "%{$search}%")
                   ->orWhere('internal_code', 'like', "%{$search}%")
                   ->orWhereHas('client', fn($qc)=>$qc->where('name','like',"%{$search}%"));
            });
        }

        return response()->json(['success' => true, 'data' => $q->paginate($perPage)]);
    }

    // GET /api/admin/equipments/{id}
    public function show($id)
    {
        $eq = Equipment::withoutGlobalScopes()->with('client')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $eq]);
    }

    // PUT /api/admin/equipments/{id}
    public function update(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'equipment_type' => 'required|string|max:255',
            'brand_model' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255',
            'internal_code' => 'nullable|string|max:255',
            'is_bidirectional' => 'boolean',
        ]);

        $eq = Equipment::withoutGlobalScopes()->findOrFail($id);
        $eq->update($request->only([
            'client_id','equipment_type','brand_model','serial_number','internal_code','is_bidirectional'
        ]));

        return response()->json(['success' => true, 'data' => $eq->load('client')]);
    }

    // DELETE /api/admin/equipments/{id}
    public function destroy($id)
    {
        $eq = Equipment::withoutGlobalScopes()->findOrFail($id);
        $eq->delete();

        return response()->json(['success' => true]);
    }
}