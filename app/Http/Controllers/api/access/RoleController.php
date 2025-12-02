<?php

namespace App\Http\Controllers\api\access;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function index()
    {
        return successResponse(
            'Liste des roles',
            Role::all()->load('permissions'),
            200
        );
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:roles,name']);
        $role = Role::create(['name' => $request->name]);

        return successResponse(['message' => 'Rôle créé avec succès', 'role' => $role->load('permissions')], 200);
    }

    public function destroy(Role $role) 
    {
        $role->delete();
        return successResponse(['message' => 'Rôle supprimé avec succès'], 200);
    }
}
