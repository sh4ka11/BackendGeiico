<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::with('client');
        
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        $equipments = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $equipments
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'equipment_type' => 'required|string|max:255',
            'brand_model' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255',
            'internal_code' => 'nullable|string|max:255',
            'is_bidirectional' => 'boolean',
        ]);
        
        // Verificar que el cliente pertenezca al usuario actual
        $client = Client::find($request->client_id);
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }
        
        $equipment = new Equipment($request->all());
        $equipment->user_id = Auth::id();
        $equipment->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Equipo creado correctamente',
            'data' => $equipment->load('client')
        ], 201);
    }
    
    public function show(Equipment $equipment)
    {
        return response()->json([
            'success' => true,
            'data' => $equipment->load('client')
        ]);
    }
    
    public function update(Request $request, Equipment $equipment)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'equipment_type' => 'required|string|max:255',
            'brand_model' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255',
            'internal_code' => 'nullable|string|max:255',
            'is_bidirectional' => 'boolean',
        ]);
        
        if ($request->client_id != $equipment->client_id) {
            // Verificar que el cliente pertenezca al usuario actual si está cambiando
            $client = Client::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }
        }
        
        $equipment->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Equipo actualizado correctamente',
            'data' => $equipment->load('client')
        ]);
    }
    
    public function destroy(Equipment $equipment)
    {
        $equipment->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Equipo eliminado correctamente'
        ]);
    }
}
