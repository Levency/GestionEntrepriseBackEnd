<?php

namespace App\Http\Controllers\api\personnel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Liste tous les départements
     */
    public function index()
    {
        $departments = Department::withCount('employees')
            ->with('manager')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Créer un département
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Département créé avec succès',
            'data' => $department
        ], 201);
    }

    /**
     * Afficher un département
     */
    public function show($id)
    {
        $department = Department::withCount('employees')
            ->with('manager')
            ->find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $department
        ]);
    }

    /**
     * Mettre à jour un département
     */
    public function update(Request $request, $id)
    {
        $department = Department::find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $department->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Département mis à jour avec succès',
            'data' => $department
        ]);
    }

    /**
     * Supprimer un département
     */
    public function destroy($id)
    {
        $department = Department::find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        if ($department->employees()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un département avec des employés'
            ], 400);
        }
        
        $department->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Département supprimé avec succès'
        ]);
    }

    /**
     * Employés d'un département
     */
    public function employees($id)
    {
        $department = Department::find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        $employees = $department->employees()
            ->with('user')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }
}
