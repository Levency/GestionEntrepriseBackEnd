<?php

namespace App\Http\Controllers\api\access;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RolePermissionController extends Controller
{
    // 🔹 Donner une ou plusieurs permissions à un rôle
    public function givePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->givePermissionTo($request->permissions);

        return successResponse([
            'message' => 'Permissions attribuées avec succès au rôle ' . $role->name,
            'role' => $role->load('permissions'),
            200
        ]);
    }

    // 🔹 Retirer une ou plusieurs permissions d’un rôle
    public function revokePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->revokePermissionTo($request->permissions);

        return successResponse([
            'message' => 'Permissions retirées avec succès du rôle ' . $role->name,
            'role' => $role->load('permissions'),
            200
        ]);
    }
}
