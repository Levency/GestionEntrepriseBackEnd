<?php

namespace App\Http\Controllers\api\access;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RolePermissionController extends Controller
{
    // 🔹 Donner une ou plusieurs permissions à un rôle
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Roles récupérées avec succès',
            'data' => Role::all()->load('permissions'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:roles,name']);
        $role = Role::create(['name' => $request->name]);

        return response()->json([
            'status' => 'succes',
            'message' => 'Rôle créé avec succès', 
            'data' => $role->load('permissions'),
        ]);
    }

    /*
    * Update role permissions
    */
    public function update(Request $request, Role $role)
    {
        $request->validate(['name' => 'required|unique:roles,name,' . $role->id]);
        $role->name = $request->name;
        $role->save();

        return response()->json ([
            'status' => 'success',
            'message' => 'Rôle mis à jour avec succès',
            'data' => $role->load('permissions'),
        ]);
    }

    public function destroy(Role $role) 
    {
        $role->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Rôle supprimé avec succès',
            'code' => 200
        ]);
    }

    public function givePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->givePermissionTo($request->permissions);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions attribuées avec succès au rôle ' . $role->name,
            'data' => $role->load('permissions'),
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

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions retirées avec succès du rôle ' . $role->name,
            'data' => $role->load('permissions'),
            200
        ]);
    }
}
