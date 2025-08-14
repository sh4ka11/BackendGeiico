<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function listUsers()
    {
        try {
            $users = User::with('roles')->get();
            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar usuarios: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Listar todos los roles
     */
    public function listRoles()
    {
        try {
            $roles = Role::all();
            return response()->json([
                'success' => true,
                'roles' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar roles: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar roles de un usuario
     */
    public function updateUserRoles(Request $request, $userId)
    {
        try {
            // Validar la entrada - cambiamos 'required' a 'present' para permitir array vacío
            $request->validate([
                'roles' => 'present|array',
                'roles.*' => 'integer|exists:roles,id'
            ]);
            
            // Encontrar el usuario
            $user = User::findOrFail($userId);
            
            // Sincronizar los roles (sync ya maneja correctamente arrays vacíos)
            $user->roles()->sync($request->roles);
            
            return response()->json([
                'success' => true,
                'message' => 'Roles actualizados correctamente',
                'user' => $user->load('roles')
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos inválidos: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar el estado de administrador
     */
    public function checkAdminStatus(Request $request)
    {
        $user = $request->user();
        $isAdmin = false;
        
        if ($user) {
            $isAdmin = $user->hasRole('admin');
        }
        
        return response()->json([
            'success' => true,
            'is_admin' => $isAdmin
        ]);
    }
    
    /**
     * Listar todos los permisos
     */
    public function listPermissions()
    {
        try {
            $permissions = \App\Models\Permission::all();
            return response()->json([
                'success' => true,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar permisos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar permisos de un rol
     */
    public function updateRolePermissions(Request $request, $roleId)
    {
        try {
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);
            
            $role = Role::findOrFail($roleId);
            $role->permissions()->sync($request->permissions);
            
            return response()->json([
                'success' => true,
                'message' => 'Permisos actualizados correctamente',
                'role' => $role->load('permissions')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo rol
     */
    public function createRole(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'slug' => 'required|string|max:255|unique:roles,slug',
            ]);
            
            $role = Role::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente',
                'role' => $role
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al crear rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo permiso
     */
    public function createPermission(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
                'slug' => 'required|string|max:255|unique:permissions,slug',
            ]);
            
            $permission = \App\Models\Permission::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Permiso creado correctamente',
                'permission' => $permission
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al crear permiso: ' . $e->getMessage()
            ], 500);
        }
    }
}