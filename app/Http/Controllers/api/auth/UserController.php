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
        return successResponse(
            UserResource::make($request->user()->load('employee')),
            null,
            'User retrieved successfully'
        );
    }

    public function getAllUsers()
    {
        $users = UserResource::collection(\App\Models\User::with('employee')->get());

        return successResponse(
            $users,
            null,
            'Users retrieved successfully'
        );
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

    public function updateUser(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'user_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|string|in:admin,user,manager',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        try {
            //code...
            $user->update($validatedData);

            return successResponse(
                UserResource::make($user->load('employee')),
                null,
                'User updated successfully'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('An error occurred while updating the user', 500);
        }
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
