<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * 🔹 Création de compte utilisateur à partir d’un employé existant.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string|max:255',
            'employee_code' => 'required|string|exists:employees,employee_code|unique:users,employee_code',
            'password' => 'required|string|min:8|confirmed|max:255',
        ]);

        // try {
            //code...
            $validator->validate();
            $employee = Employee::where('employee_code', $request->employee_code)->first();
            if (!$employee) {
                return response()->json(['message' => 'Code employé introuvable.'], 404);
            }
    
            $user = User::create([
                'user_name' => $request->user_name,
                'employee_code' => $request->employee_code,
                'password' => Hash::make($request->password),
                'role' => 'employee',
                'is_active' => true,
            ]);
    
            $token = $user->createToken('auth_token')->plainTextToken;
            dd($user);  
    
            return successResponse(
                [
                    'user' => UserResource::make($user->load('employee')),
                    'token' => $token,
                ],
                null,
                'Utilisateur créé avec succès.'
            );
        // } catch (\Illuminate\Validation\ValidationException $e) {
        //     return errorResponse('Validation error', 422);
        // }
        // Vérifie si l'employé existe bien
    }

    /**
     * 🔹 Connexion via le code employé.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('employee_code', $request->employee_code)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Code employé ou mot de passe incorrect.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => $user->load(['employee', 'roles.permissions']),
            'token' => $token,
        ]);
    }

    /**
     * 🔹 Étape 1 - Vérification du code employé avant réinitialisation.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|exists:users,employee_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json([
            'message' => 'Code employé vérifié. Vous pouvez maintenant réinitialiser le mot de passe.'
        ]);
    }

    /**
     * 🔹 Étape 2 - Réinitialisation du mot de passe.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|exists:users,employee_code',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('employee_code', $request->employee_code)->first();
        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès.'
        ]);
    }

     /**
     * Déconnexion de l'utilisateur (Logout)
     */
    public function logout(Request $request)
    {
        try {
            // Supprime uniquement le token de la session actuelle
            auth()->user()->tokens()->delete();

            return successResponse([
                'status' => true,
                'message' => 'Déconnexion réussie.',
            ], 200);

        } catch (\Exception $e) {
            return errorResponse([
                'status' => false,
                'message' => 'Erreur lors de la déconnexion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
