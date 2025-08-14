<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalibrationReport;
use App\Models\Equipment;
use App\Models\LaboratoryConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CalibrationReportAdminController extends Controller
{
    /**
     * Listado de todos los informes de calibración
     * (Sin restricción de usuario)
     */
    public function index(Request $request)
    {
        // Parámetros
        $search = $request->input('search');
        $user_id = $request->input('user_id');
        $equipment_id = $request->input('equipment_id');
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Importante: quitar scopes en el reporte y en sus relaciones
        $query = CalibrationReport::query()
            ->withoutGlobalScopes()
            ->with([
                'user',
                'laboratoryConfig' => fn($q) => $q->withoutGlobalScopes(),
                'equipment' => fn($q) => $q->withoutGlobalScopes(),
                'equipment.client' => fn($q) => $q->withoutGlobalScopes(),
            ]);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhere('calibration_location', 'like', "%{$search}%");
            });
        }
        if ($user_id)   $query->where('user_id', $user_id);
        if ($equipment_id) $query->where('equipment_id', $equipment_id);

        $query->orderBy($sortBy, $sortOrder);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)
        ]);
    }

    /**
     * Mostrar un informe específico
     */
    public function show($id)
    {
        $report = CalibrationReport::query()
            ->withoutGlobalScopes()
            ->with([
                'user',
                'laboratoryConfig' => fn($q) => $q->withoutGlobalScopes(),
                'equipment' => fn($q) => $q->withoutGlobalScopes(),
                'equipment.client' => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $report]);
    }

    /**
     * Crear nuevo informe (con posibilidad de asignar a cualquier usuario)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'laboratory_config_id' => 'required|exists:laboratory_configs,id',
            'equipment_id' => 'required|exists:equipments,id',
            'certificate_number' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'calibration_date' => 'required|date',
            'calibration_location' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $report = new CalibrationReport($request->all());
        $report->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Informe creado correctamente',
            'data' => $report->load(['equipment.client', 'laboratoryConfig', 'user'])
        ], 201);
    }

    /**
     * Actualizar informe
     */
    public function update(Request $request, $id)
    {
        $report = CalibrationReport::withoutGlobalScope('userOwned')->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'laboratory_config_id' => 'sometimes|exists:laboratory_configs,id',
            'equipment_id' => 'sometimes|exists:equipments,id',
            'certificate_number' => 'sometimes|string|max:255',
            'issue_date' => 'sometimes|date',
            'calibration_date' => 'sometimes|date',
            'calibration_location' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $report->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Informe actualizado correctamente',
            'data' => $report->load(['equipment.client', 'laboratoryConfig', 'user'])
        ]);
    }

    /**
     * Eliminar informe
     */
    public function destroy($id)
    {
        $report = CalibrationReport::withoutGlobalScope('userOwned')->findOrFail($id);
        $report->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Informe eliminado correctamente'
        ]);
    }

    /**
     * Obtener datos para formulario de creación/edición
     */
    public function getFormData()
    {
        // Obtener listas para los selects del formulario
        $users = User::select('id', 'name', 'email')->get();
        $equipments = Equipment::with('client:id,name')
                               ->select('id', 'client_id', 'equipment_type', 'brand_model', 'serial_number')
                               ->get();
        $labConfigs = LaboratoryConfig::select('id', 'lab_name')->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'equipments' => $equipments,
                'laboratory_configs' => $labConfigs
            ]
        ]);
    }
    
    /**
     * Generar PDF/Excel
     */
    public function generateExport($id)
    {
        $report = CalibrationReport::withoutGlobalScope('userOwned')
                                   ->with(['equipment.client', 'laboratoryConfig', 'user'])
                                   ->findOrFail($id);
                                   
        $exportData = [
            'encabezado' => [
                'lab_name' => $report->laboratoryConfig->lab_name,
                'onac_number' => $report->laboratoryConfig->onac_number,
                'document_title' => $report->laboratoryConfig->document_title,
                'certificate_number' => $report->certificate_number,
                'issue_date' => $report->issue_date->format('Y-m-d'),
                'created_by' => $report->user->name,
            ],
            'datos_cliente_equipo' => [
                'client_name' => $report->equipment->client->name,
                'client_address' => $report->equipment->client->address,
                'equipment_type' => $report->equipment->equipment_type,
                'brand_model' => $report->equipment->brand_model,
                'serial_number' => $report->equipment->serial_number,
                'internal_code' => $report->equipment->internal_code,
                'is_bidirectional' => $report->equipment->is_bidirectional ? 'Sí' : 'No',
                'calibration_date' => $report->calibration_date->format('Y-m-d'),
                'calibration_location' => $report->calibration_location,
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $exportData
        ]);
    }
}
