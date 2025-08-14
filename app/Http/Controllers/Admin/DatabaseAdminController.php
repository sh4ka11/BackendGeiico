<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DatabaseAdminController extends Controller
{
    /**
     * Listar todas las tablas de la base de datos
     */
    public function index()
    {
        try {
            // Método más confiable para obtener tablas en MySQL/MariaDB
            $tables = DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = ?
                ORDER BY table_name ASC
            ", [env('DB_DATABASE')]);
            
            $tableList = [];
            
            foreach ($tables as $table) {
                $tableList[] = $table->table_name;
            }
            
            // Añadir información de diagnóstico
            Log::info('Tablas encontradas:', [
                'database' => env('DB_DATABASE'),
                'count' => count($tableList),
                'tables' => $tableList
            ]);
            
            return response()->json([
                'success' => true,
                'tables' => $tableList,
                'count' => count($tableList),
                'database' => env('DB_DATABASE'),
                'connection' => config('database.default')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo tablas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo tablas: ' . $e->getMessage(),
                'database' => env('DB_DATABASE'),
                'connection' => config('database.default')
            ], 500);
        }
    }

    /**
     * Obtener estructura de una tabla específica
     */
    public function getTableStructure($table)
    {
        try {
            if (!Schema::hasTable($table)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tabla no encontrada'
                ], 404);
            }
            
            $columns = Schema::getColumnListing($table);
            $structure = [];
            
            foreach ($columns as $column) {
                $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
                if (count($type) > 0) {
                    $structure[] = [
                        'name' => $column,
                        'type' => $type[0]->Type,
                        'nullable' => $type[0]->Null == 'YES',
                        'default' => $type[0]->Default,
                        'key' => $type[0]->Key
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'table' => $table,
                'structure' => $structure
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estructura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estructura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos de una tabla específica
     */
    public function getTableData(Request $request, $table)
    {
        try {
            // Verificar si la tabla existe
            if (!Schema::hasTable($table)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tabla no encontrada: ' . $table
                ], 404);
            }
            
            // Parámetros de paginación y filtrado
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);
            $sortField = $request->input('sort', 'id');
            $sortDir = $request->input('direction', 'asc');
            $search = $request->input('search');
            
            $offset = ($page - 1) * $limit;
            
            // Construir consulta base
            $query = DB::table($table);
            
            // Aplicar búsqueda si se proporciona
            if ($search && strlen($search) >= 3) {
                $columns = Schema::getColumnListing($table);
                $query->where(function($q) use ($columns, $search) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                });
            }
            
            // Obtener total antes de aplicar paginación
            $total = $query->count();
            
            // Aplicar ordenamiento si el campo existe
            if (Schema::hasColumn($table, $sortField)) {
                $query->orderBy($sortField, $sortDir);
            }
            
            // Aplicar paginación
            $data = $query->skip($offset)->take($limit)->get();
            
            // Obtener información de columnas para el frontend
            $columns = [];
            foreach (Schema::getColumnListing($table) as $column) {
                $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column])[0];
                $columns[] = [
                    'name' => $column,
                    'type' => $type->Type,
                    'key' => $type->Key,
                    'editable' => !in_array($column, ['id', 'created_at', 'updated_at'])
                ];
            }
            
            return response()->json([
                'success' => true,
                'table' => $table,
                'columns' => $columns,
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de tabla: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo datos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar un registro en una tabla
     */
    public function updateRecord(Request $request, $table, $id)
    {
        try {
            if (!Schema::hasTable($table)) {
                return response()->json(['success' => false, 'error' => 'Tabla no encontrada'], 404);
            }
            
            $data = $request->except(['_method', '_token']);
            DB::table($table)->where('id', $id)->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error actualizando registro: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Error actualizando registro: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar un registro de una tabla
     */
    public function deleteRecord($table, $id)
    {
        try {
            if (!Schema::hasTable($table)) {
                return response()->json(['success' => false, 'error' => 'Tabla no encontrada'], 404);
            }
            
            DB::table($table)->where('id', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error eliminando registro: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Error eliminando registro: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Optimizar una tabla
     */
    public function optimizeTable($table)
    {
        try {
            if (!Schema::hasTable($table)) {
                return response()->json(['success' => false, 'error' => 'Tabla no encontrada'], 404);
            }
            
            DB::statement("OPTIMIZE TABLE `{$table}`");
            
            return response()->json([
                'success' => true,
                'message' => 'Tabla optimizada correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error optimizando tabla: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Error optimizando tabla: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Ejecutar mantenimiento en la base de datos
     */
    public function runMaintenance()
    {
        try {
            // Obtener todas las tablas
            $tables = DB::select('SHOW TABLES');
            $property = 'Tables_in_' . env('DB_DATABASE');
            $tableNames = [];
            
            foreach ($tables as $table) {
                if (property_exists($table, $property)) {
                    $tableNames[] = $table->$property;
                }
            }
            
            // Optimizar cada tabla
            $optimized = [];
            foreach ($tableNames as $tableName) {
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $optimized[] = $tableName;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento completado',
                'tables_optimized' => $optimized
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error durante mantenimiento: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Error durante mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas del sistema
     */
    public function getSystemStats()
    {
        try {
            // Obtener número de tablas
            $tables = DB::select('SHOW TABLES');
            $tableCount = count($tables);
            
            // Obtener tamaño de la base de datos
            $dbSize = DB::select("SELECT SUM(data_length + index_length) / 1024 / 1024 as size_mb FROM information_schema.TABLES WHERE table_schema = ?", [env('DB_DATABASE')]);
            
            // Estadísticas de usuarios
            $userCount = DB::table('users')->count();
            $activeUsers = DB::table('users')->where('active', 1)->count();
            
            // Tablas más grandes
            $largestTables = DB::select("
                SELECT 
                    table_name, 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM 
                    information_schema.TABLES 
                WHERE 
                    table_schema = ?
                ORDER BY 
                    (data_length + index_length) DESC
                LIMIT 5
            ", [env('DB_DATABASE')]);
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'database_name' => env('DB_DATABASE'),
                    'total_tables' => $tableCount,
                    'database_size_mb' => $dbSize[0]->size_mb,
                    'user_count' => $userCount,
                    'active_users' => $activeUsers,
                    'largest_tables' => $largestTables
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Ejecutar consulta SQL personalizada (solo para administradores)
     */
    public function executeQuery(Request $request)
    {
        try {
            $query = $request->input('query');
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se proporcionó ninguna consulta'
                ], 400);
            }
            
            // Verificar que no sea una consulta peligrosa
            $dangerousCommands = ['DROP', 'TRUNCATE', 'DELETE FROM', 'ALTER'];
            foreach ($dangerousCommands as $command) {
                if (stripos($query, $command) !== false) {
                    Log::warning("Intento de ejecutar consulta peligrosa: {$query}");
                    return response()->json([
                        'success' => false,
                        'error' => 'Consulta no permitida por razones de seguridad'
                    ], 403);
                }
            }
            
            // Ejecutar la consulta
            $result = DB::select($query);
            
            return response()->json([
                'success' => true,
                'result' => $result,
                'count' => count($result)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error ejecutando consulta SQL: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'Error en la consulta: ' . $e->getMessage()
            ], 500);
        }
    }
}