<?php

namespace App\Http\Controllers\api\access;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return successResponse(
            "Listes des Permissions",
            Permission::all()->load('roles'),
            200
        );
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:permissions,name']);
        $permission = Permission::create(['name' => $request->name]);
        return successResponse(['message' => 'Permission créée avec succès', 'permission' => $permission], 200);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(['message' => 'Permission supprimée avec succès']);
    }
}
