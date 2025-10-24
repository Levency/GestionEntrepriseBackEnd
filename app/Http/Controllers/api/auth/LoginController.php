<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // --- IGNORE ---
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            //code...
            if (auth()->attempt($credentials)) {
                $user = auth()->user();
                $token = $user->createToken('auth_token')->plainTextToken;
                $tokenType = 'Bearer';
    
                return successResponse(
                    UserResource::make($user->load('employee')),
                    $token,
                    'Login successful'
                );
            }
    
            return errorResponse('Invalid credentials', 401);
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('An error occurred during login', 500);
        }
    }
}
