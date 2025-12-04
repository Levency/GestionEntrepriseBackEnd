<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => $user->load('roles','permissions','employee'),
            'message' => 'User profile retrieved successfully',
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'user_name' => 'sometimes|string|max:255',
        ]);

        $user->update($validatedData);

        return response()->json([
            'status' => 'success',
            'data' => $user->load('roles','permissions','employee'),
            'message' => 'User profile updated successfully',
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!\Hash::check($validatedData['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => bcrypt($validatedData['new_password']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully',
            'data' => $user->load('roles','permissions','employee')
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }

}
