<?php

namespace App\Http\Controllers\api\personnel;

use App\Models\Departement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResouce;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DepartementResouce;

class DepartmentController extends Controller
{
    /**
     * Liste tous les départements
     */
    public function index()
    {
        $deparments = Departement::latest('created_at')->get();
        return response()->json([
            'status' => 'success',
            'data' => $deparments,
            'message' => 'Départements récupérés avec succès',
        ], 200);
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
            $department = Departement::create([
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $request->icon
            ]);
            
            return response()->json([
                'status' => 'success',
                'data' => new DepartementResouce($department),
                'message' => 'Département créé avec succès',
            ], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du département',
            ], 500);
        }

    }

    /**
     * Afficher un département
     */
    public function show(Departement $department)
    {
       return response()->json([
            'status' => 'success',
            'data' => new DepartementResouce($department),
            'message' => 'Département récupéré avec succès',
        ], 200);
    }

    /**
     * Mettre à jour un département
     */
    public function update(Request $request, Departement $department)
    {
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string'
        ]);

        try {
            //code...
            $department->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'data' => new DepartementResouce($department),
                'message' => 'Département mis à jour avec succès',
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du département',
            ], 500);
        }

    }

    /**
     * Supprimer un département
     */
    public function destroy(Departement $department)
    {
        try {
            //code...
            $department->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Département supprimé avec succès',
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du département',
            ], 500);
        }
    }

    /**
     * Employés d'un département
     */
    public function employees(Departement $department)
    {
        return response()->json([
            'status' => 'success',
            'data' => EmployeeResouce::collection($department->employees),
            'message' => 'Employés du département récupérés avec succès',
        ], 200);
    }
}
