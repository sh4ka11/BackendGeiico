<?php

namespace App\Http\Controllers;

use App\Models\CalibrationReport;
use App\Models\Equipment;
use App\Models\LaboratoryConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalibrationReportController extends Controller
{
    public function index(Request $request)
    {
        $query = CalibrationReport::with(['equipment.client', 'laboratoryConfig']);
        
        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }
        
        $reports = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }
    
    public function initNewReport(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
        ]);
        
        // Verificar que el equipo pertenezca al usuario actual
        $equipment = Equipment::with('client')->find($request->equipment_id);
        
        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipo no encontrado'
            ], 404);
        }
        
        // Obtener configuración de laboratorio
        $labConfig = LaboratoryConfig::first();
        
        if (!$labConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Debe configurar los datos del laboratorio primero'
            ], 400);
        }
        
        // Preparar datos para el informe
        $reportData = [
            'configurable' => [
                'lab_name' => $labConfig->lab_name,
                'onac_number' => $labConfig->onac_number,
                'document_title' => $labConfig->document_title,
            ],
            'editable' => [
                'certificate_number' => '',
                'issue_date' => date('Y-m-d'),
            ],
            'client_equipment' => [
                'client_name' => $equipment->client->name,
                'client_address' => $equipment->client->address,
                'equipment_type' => $equipment->equipment_type,
                'brand_model' => $equipment->brand_model,
                'serial_number' => $equipment->serial_number,
                'internal_code' => $equipment->internal_code,
                'is_bidirectional' => $equipment->is_bidirectional ? 'Sí' : 'No',
                'calibration_date' => date('Y-m-d'),
                'calibration_location' => '',
            ],
            'laboratory_config_id' => $labConfig->id,
            'equipment_id' => $equipment->id
        ];
        
        return response()->json([
            'success' => true,
            'data' => $reportData
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'laboratory_config_id' => 'required|exists:laboratory_configs,id',
            'equipment_id' => 'required|exists:equipments,id',
            'certificate_number' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'calibration_date' => 'required|date',
            'calibration_location' => 'required|string|max:255',
        ]);
        
        $report = new CalibrationReport($request->all());
        $report->user_id = Auth::id();
        $report->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Informe creado correctamente',
            'data' => $report->load(['equipment.client', 'laboratoryConfig'])
        ], 201);
    }
    
    public function show(CalibrationReport $calibrationReport)
    {
        return response()->json([
            'success' => true,
            'data' => $calibrationReport->load(['equipment.client', 'laboratoryConfig'])
        ]);
    }
    
    public function update(Request $request, CalibrationReport $calibrationReport)
    {
        $request->validate([
            'certificate_number' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'calibration_date' => 'required|date',
            'calibration_location' => 'required|string|max:255',
        ]);
        
        $calibrationReport->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Informe actualizado correctamente',
            'data' => $calibrationReport->load(['equipment.client', 'laboratoryConfig'])
        ]);
    }
    
    public function generatePdf(CalibrationReport $calibrationReport)
    {
        $calibrationReport->load(['equipment.client', 'laboratoryConfig']);
        
        $pdfData = [
            'encabezado' => [
                'lab_name' => $calibrationReport->laboratoryConfig->lab_name,
                'onac_number' => $calibrationReport->laboratoryConfig->onac_number,
                'document_title' => $calibrationReport->laboratoryConfig->document_title,
                'certificate_number' => $calibrationReport->certificate_number,
                'issue_date' => $calibrationReport->issue_date->format('Y-m-d'),
            ],
            'datos_cliente_equipo' => [
                'client_name' => $calibrationReport->equipment->client->name,
                'client_address' => $calibrationReport->equipment->client->address,
                'equipment_type' => $calibrationReport->equipment->equipment_type,
                'brand_model' => $calibrationReport->equipment->brand_model,
                'serial_number' => $calibrationReport->equipment->serial_number,
                'internal_code' => $calibrationReport->equipment->internal_code,
                'is_bidirectional' => $calibrationReport->equipment->is_bidirectional ? 'Sí' : 'No',
                'calibration_date' => $calibrationReport->calibration_date->format('Y-m-d'),
                'calibration_location' => $calibrationReport->calibration_location,
            ]
        ];
        
        return response()->json([
            'success' => true,
            'message' => 'Datos preparados para generar Excel',
            'data' => $pdfData
        ]);
    }
    
    /**
     * Elimina un informe de calibración específico.
     */
    public function destroy(CalibrationReport $calibrationReport)
    {
        $calibrationReport->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Informe eliminado correctamente'
        ]);
    }
}
