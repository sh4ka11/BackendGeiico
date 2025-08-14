<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Deshabilitar revisión de claves foráneas para poder insertar IDs específicos
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Limpiar tablas existentes
        Role::truncate();
        Permission::truncate();
        DB::table('permission_role')->truncate();
        
        // Crear roles con IDs específicos
        $roles = [
            // ID 1 para Administrador
            ['id' => 1, 'name' => 'Administrador', 'slug' => 'admin', 'description' => 'Acceso completo al sistema'],
            // ID 2 para Visualizador (antes era editor)
            ['id' => 2, 'name' => 'Visualizador', 'slug' => 'view-all-files', 'description' => 'Puede ver todos los archivos pero no administrar usuarios']
        ];
        
        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
        
        // Crear permisos básicos
        $permissions = [
            ['name' => 'Gestionar Usuarios', 'slug' => 'manage-users', 'description' => 'Permite gestionar usuarios del sistema'],
            ['name' => 'Gestionar Roles', 'slug' => 'manage-roles', 'description' => 'Permite gestionar roles del sistema'],
            ['name' => 'Gestionar Permisos', 'slug' => 'manage-permissions', 'description' => 'Permite gestionar permisos del sistema'],
            ['name' => 'Administrar Base de Datos', 'slug' => 'manage-database', 'description' => 'Permite gestionar la base de datos del sistema'],
            ['name' => 'Ver Archivos', 'slug' => 'view-files', 'description' => 'Permite ver archivos en el sistema'],
        ];
        
        $permissionIds = [];
        
        foreach ($permissions as $permData) {
            $permission = Permission::create($permData);
            $permissionIds[] = $permission->id;
        }
        
        // Asignar todos los permisos al rol de administrador
        $adminRole = Role::find(1);
        $adminRole->permissions()->sync($permissionIds);
        
        // Asignar solo el permiso de ver archivos al rol de visualizador
        $viewerRole = Role::find(2);
        $viewerRole->permissions()->sync([5]); // Permiso "Ver Archivos"
        
        // Habilitar nuevamente revisión de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->command->info('Roles y permisos básicos creados correctamente');
    }
}
