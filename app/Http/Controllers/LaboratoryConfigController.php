<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaboratoryConfigController extends Controller
{
    public function index()
    {
        $config = LaboratoryConfig::first(); // El scope global ya filtra por usuario actual+


        
        
        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'No hay configuración guardada'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'lab_name' => 'required|string|max:255',
            'onac_number' => 'required|string|max:255',
            'document_title' => 'required|string|max:255',
        ]);
        
        // Verificar si ya existe configuración para este usuario
        // El scope global ya se aplicará aquí
        $existing = LaboratoryConfig::first();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una configuración. Use el método PUT para actualizar.'
            ], 409);
        }
        
        $config = new LaboratoryConfig($request->all());
        $config->user_id = Auth::id();
        $config->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente',
            'data' => $config
        ], 201);
    }
    
    public function update(Request $request, LaboratoryConfig $laboratoryConfig)
    {
        $request->validate([
            'lab_name' => 'required|string|max:255',
            'onac_number' => 'required|string|max:255',
            'document_title' => 'required|string|max:255',
        ]);
        
        $laboratoryConfig->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente',
            'data' => $laboratoryConfig
        ]);
    }
}
