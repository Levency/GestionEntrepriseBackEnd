<?php

namespace App\Http\Controllers\api\personnel;

use App\Models\Departement;
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
        $departments = Departement::all();
        
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
            'icon' => 'required|string'
        ]);

        try {
            //code...
            $department = Departement::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Département créé avec succès',
                'data' => $department
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }

    /**
     * Afficher un département
     */
    public function show($id)
    {
        $department = Departement::find($id);
        
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
        $department = Departement::find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'required|string'
        ]);

        try {
            //code...
            $department->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Département mis à jour avec succès',
                'data' => $department
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }

    }

    /**
     * Supprimer un département
     */
    public function destroy($id)
    {
        $department = Departement::find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        // if ($department->employees()->count() > 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Impossible de supprimer un département avec des employés'
        //     ], 400);
        // }
        
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
        $department = Departement::find($id);
        
        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
        
        $employees = $department->employees()
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }
}
