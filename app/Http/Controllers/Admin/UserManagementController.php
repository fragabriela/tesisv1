<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with(['roles', 'alumno', 'tutor.alumnos'])->select(['id', 'name', 'email', 'created_at']);
            
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('roles', function($user) {
                    $roles = $user->roles->map(function($role) {
                        $badgeColors = [
                            'administrador' => 'danger',
                            'coordinador' => 'warning',
                            'tutor' => 'info',
                            'alumno' => 'success'
                        ];
                        $color = $badgeColors[$role->name] ?? 'secondary';
                        return '<span class="badge badge-' . $color . '">' . ucfirst($role->name) . '</span>';
                    })->join(' ');
                    
                    return $roles ?: '<span class="badge badge-secondary">Sin roles</span>';
                })
                ->addColumn('associated_records', function($user) {
                    $records = [];
                    
                    // User as Alumno
                    if ($user->alumno) {
                        $records[] = '<span class="text-info"><i class="fas fa-user-graduate"></i> Alumno: ' . $user->alumno->nombre . ' ' . $user->alumno->apellido . '</span>';
                    }
                    
                    // User as Tutor
                    if ($user->tutor) {
                        $tutorRecord = '<span class="text-warning"><i class="fas fa-chalkboard-teacher"></i> Tutor: ' . $user->tutor->nombre . ' ' . $user->tutor->apellido . '</span>';
                        
                        // Add associated alumnos count
                        $alumnosCount = $user->tutor->alumnos->count();
                        if ($alumnosCount > 0) {
                            $tutorRecord .= ' <small class="text-muted">(' . $alumnosCount . ' alumno' . ($alumnosCount > 1 ? 's' : '') . ')</small>';
                        }
                        
                        $records[] = $tutorRecord;
                    }
                    
                    return !empty($records) ? implode('<br>', $records) : '<span class="text-muted">Sin asociaciones</span>';
                })
                ->addColumn('action', function($user) {
                    $actionBtn = '<div class="btn-group" role="group">';
                    $actionBtn .= '<button class="btn btn-sm btn-primary" onclick="editUser(' . $user->id . ')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>';
                    $actionBtn .= '<button class="btn btn-sm btn-info" onclick="manageRoles(' . $user->id . ')" title="Gestionar Roles">
                        <i class="fas fa-user-cog"></i>
                    </button>';
                    $actionBtn .= '<button class="btn btn-sm btn-warning" onclick="associateRecords(' . $user->id . ')" title="Asociar Registros">
                        <i class="fas fa-link"></i>
                    </button>';
                    if ($user->id != auth()->id()) {
                        $actionBtn .= '<button class="btn btn-sm btn-danger" onclick="deleteUser(' . $user->id . ')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>';
                    }
                    $actionBtn .= '</div>';
                    
                    return $actionBtn;
                })
                ->rawColumns(['roles', 'associated_records', 'action'])
                ->make(true);
        }
        
        return view('admin.users.index');
    }

    /**
     * Show user details
     */
    public function show($id)
    {
        $user = User::with(['roles', 'alumno', 'tutor'])->findOrFail($id);
        return response()->json([
            'user' => $user,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    }

    /**
     * Update user roles
     */
    public function updateRoles(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $user = User::findOrFail($id);
            $user->syncRoles($request->roles);

            return response()->json([
                'success' => true,
                'message' => 'Roles actualizados exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Associate user with alumno or tutor records
     */
    public function associateRecords(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'alumno_ids' => 'nullable|array',
            'alumno_ids.*' => 'exists:alumnos,id',
            'tutor_id' => 'nullable|exists:tutores,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $user = User::with(['alumno', 'tutor'])->findOrFail($id);

            // Handle Alumno Association (1:1 relationship with user)
            if ($request->filled('alumno_ids') && count($request->alumno_ids) > 0) {
                // For the User-Alumno relationship (1:1), associate with the first selected alumno
                $firstAlumnoId = $request->alumno_ids[0];
                
                // Remove previous association
                Alumno::where('user_id', $user->id)->update(['user_id' => null]);
                
                // Create new association
                $alumno = Alumno::findOrFail($firstAlumnoId);
                $alumno->user_id = $user->id;
                $alumno->save();
            } else {
                // Remove alumno association
                Alumno::where('user_id', $user->id)->update(['user_id' => null]);
            }

            // Handle Tutor Association and Multiple Alumnos
            if ($request->tutor_id) {
                $tutor = Tutor::findOrFail($request->tutor_id);
                
                // Associate user with tutor (1:1)
                Tutor::where('user_id', $user->id)->update(['user_id' => null]);
                $tutor->user_id = $user->id;
                $tutor->save();
                
                // Associate tutor with multiple alumnos (many-to-many)
                if ($request->filled('alumno_ids')) {
                    // Sync the relationship (removes old associations and adds new ones)
                    $tutor->alumnos()->sync($request->alumno_ids);
                } else {
                    // Remove all alumno associations for this tutor
                    $tutor->alumnos()->detach();
                }
            } else {
                // Remove tutor association
                $oldTutor = Tutor::where('user_id', $user->id)->first();
                if ($oldTutor) {
                    $oldTutor->user_id = null;
                    $oldTutor->save();
                    
                    // Optionally, you might want to keep the tutor-alumno relationships
                    // or remove them. For now, we'll keep them.
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Asociaciones actualizadas exitosamente',
                'associations' => [
                    'alumno' => $user->fresh('alumno')->alumno,
                    'tutor' => $user->fresh('tutor.alumnos')->tutor,
                    'tutor_alumnos_count' => $user->fresh('tutor.alumnos')->tutor ? $user->tutor->alumnos->count() : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar asociaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all roles
     */
    public function getRoles()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    /**
     * Get all permissions
     */
    public function getPermissions()
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        });
        
        return response()->json($permissions);
    }

    /**
     * Show roles management page
     */
    public function rolesIndex(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::withCount('users')->get();
            
            return DataTables::of($roles)
                ->addIndexColumn()
                ->addColumn('users_count', function($role) {
                    return '<span class="badge badge-info">' . $role->users_count . '</span>';
                })
                ->addColumn('permissions_count', function($role) {
                    $count = $role->permissions()->count();
                    return '<span class="badge badge-secondary">' . $count . '</span>';
                })
                ->addColumn('action', function($role) {
                    $actionBtn = '<div class="btn-group" role="group">';
                    $actionBtn .= '<button class="btn btn-sm btn-primary" onclick="editRole(' . $role->id . ')" title="Editar Rol">
                        <i class="fas fa-edit"></i>
                    </button>';
                    $actionBtn .= '<button class="btn btn-sm btn-info" onclick="manageRolePermissions(' . $role->id . ')" title="Gestionar Permisos">
                        <i class="fas fa-key"></i>
                    </button>';
                    
                    // No permitir eliminar roles del sistema
                    if (!in_array($role->name, ['administrador', 'coordinador', 'tutor', 'alumno'])) {
                        $actionBtn .= '<button class="btn btn-sm btn-danger" onclick="deleteRole(' . $role->id . ')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>';
                    }
                    $actionBtn .= '</div>';
                    
                    return $actionBtn;
                })
                ->rawColumns(['users_count', 'permissions_count', 'action'])
                ->make(true);
        }
        
        return view('admin.roles.index');
    }

    /**
     * Create new role
     */
    public function storeRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $role = Role::create(['name' => $request->name]);
            
            if ($request->permissions) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol creado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update role
     */
    public function updateRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $role = Role::findOrFail($id);
            $role->name = $request->name;
            $role->save();

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update role permissions
     */
    public function updateRolePermissions(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $role = Role::findOrFail($id);
            
            // Log para debug
            Log::info('Updating role permissions', [
                'role_id' => $id,
                'role_name' => $role->name,
                'old_permissions' => $role->permissions->pluck('name')->toArray(),
                'new_permissions' => $request->permissions ?? []
            ]);
            
            $role->syncPermissions($request->permissions ?? []);
            
            // Clear permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Verify update
            $role->refresh();
            $currentPermissions = $role->permissions->pluck('name')->toArray();
            
            Log::info('Role permissions updated', [
                'role_id' => $id,
                'role_name' => $role->name,
                'final_permissions' => $currentPermissions
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permisos del rol actualizados exitosamente',
                'debug' => [
                    'permissions_count' => count($currentPermissions),
                    'permissions' => $currentPermissions
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating role permissions', [
                'role_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al actualizar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role with permissions
     */
    public function showRole($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json([
            'role' => $role,
            'permissions' => $role->permissions->pluck('name')
        ]);
    }

    /**
     * Delete role
     */
    public function destroyRole($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // No permitir eliminar roles del sistema
            if (in_array($role->name, ['administrador', 'coordinador', 'tutor', 'alumno'])) {
                return response()->json(['error' => 'No se pueden eliminar los roles del sistema'], 422);
            }
            
            // Verificar si hay usuarios con este rol
            if ($role->users()->count() > 0) {
                return response()->json(['error' => 'No se puede eliminar un rol que tiene usuarios asignados'], 422);
            }
            
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available alumnos and tutores for association
     */
    public function getAssociationData($id)
    {
        $user = User::with(['alumno', 'tutor.alumnos'])->findOrFail($id);
        
        // Get ALL alumnos (no restrictions for multiple associations)
        $alumnos = Alumno::select(['id', 'nombre', 'apellido', 'email', 'matricula'])
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();
            
        // Get ALL tutores (no restrictions for multiple associations)  
        $tutores = Tutor::select(['id', 'nombre', 'apellido', 'email', 'especialidad'])
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return response()->json([
            'user' => $user,
            'alumnos' => $alumnos,
            'tutores' => $tutores
        ]);
    }

    /**
     * Update user information
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $user = User::findOrFail($id);
            $user->name = $request->name;
            $user->email = $request->email;
            
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $user->assignRole($request->roles);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        try {
            if ($id == auth()->id()) {
                return response()->json(['error' => 'No puedes eliminar tu propio usuario'], 422);
            }

            $user = User::findOrFail($id);
            
            // Remove associations before deleting
            Alumno::where('user_id', $id)->update(['user_id' => null]);
            Tutor::where('user_id', $id)->update(['user_id' => null]);
            
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar usuario: ' . $e->getMessage()
            ], 500);
        }
    }
}
