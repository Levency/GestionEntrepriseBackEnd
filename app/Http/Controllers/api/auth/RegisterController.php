<?php

namespace App\Http\Controllers\api\auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class RegisterController extends Controller
{
    /*
     * Handle the registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function register(Request $request)
    {
        // Validation des données d'inscription
        $validatedData = $request->validate([
            'user_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|string|in:admin,user,manager',
            'status' => 'required|string|in:active,inactive',
        ]);

        // Création de l'utilisateur
        try {
            //code...
            $user = User::create($request->all());
    
            // Retourner une réponse appropriée
            return successResponse(
                UserResource::make($user->load('employee')),
                 $user->createToken('auth_token')->plainTextToken,    
                'Utilisateur créé avec succès',
                // 200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la création de l\'utilisateur', 500);
        }
    }
}