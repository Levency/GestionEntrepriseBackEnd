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
        $deparments = Departement::with('employees')->get();
        return successResponse(
            DepartementResouce::collection($deparments->load('employees')),
            'Départements récupérés avec succès',
            200
        );
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
            
            return successResponse(
                'Département créé avec succès',
                new DepartementResouce($department),
                201
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la création du département', 500);
        }

    }

    /**
     * Afficher un département
     */
    public function show(Departement $department)
    {
       return successResponse(
           new DepartementResouce($department),
           'Département récupéré avec succès',
           200
       );
    }

    /**
     * Mettre à jour un département
     */
    public function update(Request $request, Departement $department)
    {
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string',
            'icon' => 'required|string'
        ]);

        try {
            //code...
            $department->update($request->all());
            
            return successResponse(
                'Département mis à jour avec succès',
                new DepartementResouce($department)
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la mise à jour du département', 500);
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
            return successResponse(
                null,
                'Département supprimé avec succès'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la suppression du département', 500);
        }
    }

    /**
     * Employés d'un département
     */
    public function employees(Departement $department)
    {
        return successResponse(
            EmployeeResouce::collection($department->employees),
            'Employés du département récupérés avec succès',
            200
        );
    }
}
