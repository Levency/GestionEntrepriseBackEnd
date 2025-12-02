<?php

namespace App\Http\Controllers\api\access;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserRoleController extends Controller
{
    public function assignRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|exists:roles,name']);
        $user->assignRole($request->role);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Rôle attribué avec succès',
            'data' => $user
            ]);
    }

    public function removeRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|exists:roles,name']);
        $user->removeRole($request->role);
        return response()->json([
            'status' => 'success',
            'message' => 'Rôle retirer avec succès',
            'data' => $user
            ]);
    }

    public function givePermission(Request $request, User $user)
    {
        $request->validate(['permission' => 'required|exists:permissions,name']);
        $user->givePermissionTo($request->permission);
        return response()->json(['message' => 'Permission attribuée avec succès']);
    }

    public function revokePermission(Request $request, User $user)
    {
        $request->validate(['permission' => 'required|exists:permissions,name']);
        $user->revokePermissionTo($request->permission);
        return response()->json(['message' => 'Permission retirée avec succès']);
    }
}
