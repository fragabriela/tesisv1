<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ROLES = ['administrador', 'coordinador', 'profesor', 'tutor', 'alumno'];

    private const PERMISSIONS = [
        'ver documentos',
        'crear documentos',
        'editar documentos',
        'eliminar documentos',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $permission) => Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]));

        Role::whereIn('name', self::ROLES)->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions->all()));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $newPermissionsByRole = [
            'coordinador' => ['eliminar documentos'],
            'profesor' => self::PERMISSIONS,
            'tutor' => ['crear documentos', 'eliminar documentos'],
            'alumno' => ['crear documentos', 'eliminar documentos'],
        ];

        foreach ($newPermissionsByRole as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            $permissions = Permission::whereIn('name', $permissionNames)->get();

            if ($role) {
                $role->revokePermissionTo($permissions->all());
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
