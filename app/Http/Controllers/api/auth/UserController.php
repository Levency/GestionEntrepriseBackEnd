<?php

namespace App\Http\Controllers\api\auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // --- IGNORE ---
    public function getUser(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'data' => new UserResource($request->user()->with('roles','permissions','employee')),
        ]);
    }

    public function getAllUsers()
    {
        $users = UserResource::collection(\App\Models\User::with('employee')->get());

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $users,
        ]);
    }


    public function deleteUser(User $user)
    {
        if (!$user) {
            return errorResponse('User not found', 404);
        }

        try {
            //code...
            $user->delete();

            return successResponse(
                null,
                null,
                'User deleted successfully'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('An error occurred while deleting the user', 500);
        }
    }

    /*
    * Deactivate or Activate User
    */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User status updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function showUser(User $user)
    {
        return successResponse(
            UserResource::make($user->load('employee')),
            null,
            'User retrieved successfully'
        );
    }
}
