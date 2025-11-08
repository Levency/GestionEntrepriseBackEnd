<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Response;

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
            'password' => 'required|string|min:8|confirmed',
        ]);

        // if ($validator->fails()) {
        //     return errorResponse('Erreur de validation', 422, $validator->errors());
        // }

        try {
            $employee = Employee::where('employee_code', $request->employee_code)->first();

            if (!$employee) {
                return response()->json([
                'success' => false,
                'message' => 'Employé non trouvé.',
                'status' => 404
            ]);
            }

            $user = User::create([
                'user_name' => $request->user_name,
                'employee_code' => $request->employee_code,
                'password' => Hash::make($request->password),
                'role' => 'employee',
                'is_active' => true,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Compte utilisateur créé avec succès.',
                'status' => 201,
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'erreuur serveur',
                'status' => 500
            ]);
                
        }
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
            return response()->json([
                'success' => false,
                'message' => 'Code employé ou mot de passe incorrect.',
                'status' => 201,
            ], 201);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
                'success' => true,
                'message' => 'Compte utilisateur connecter avec success.',
                'status' => 201,
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
            ], 201);
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
