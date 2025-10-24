<?php

namespace App\Http\Controllers\api\taxes;

use App\Models\Taxe;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaxeResource;
use Illuminate\Support\Facades\Validator;

class TaxeController extends Controller
{
    public function index()
    {
        return successResponse(
            'Liste des taxes', 
            TaxeResource::collection(Taxe::all()),
        200
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:taxes,name',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        try {
            //code...
            $taxe = Taxe::create($request->all());
            return successResponse(
                new TaxeResource($taxe),
                'Taxe créée avec succès',
                201
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la création de la taxe', 500);
        }
    }

    public function show(Taxe $taxe)
    {
        return successResponse(
            new TaxeResource($taxe),
            'Taxe récupérée avec succès',
            200
        );
    }

    public function update(Request $request, Taxe $taxe)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:taxes,name,' . $taxe->id,
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        try {
            //code...
            $taxe->update($request->all());
            return successResponse(
                new TaxeResource($taxe),
                'Taxe mise à jour avec succès',
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la mise à jour de la taxe', 500);
        }
    }
    public function destroy(Taxe $taxe)
    {
        try {
            //code...
            $taxe->delete();
            return successResponse(
                null,
                'Taxe supprimée avec succès',
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la suppression de la taxe', 500);
        }
    }

    public function activate(Taxe $taxe)
    {
        try {
            //code...
            $taxe->update(['is_active' => true]);
            return successResponse(
                new TaxeResource($taxe),
                'Taxe activée avec succès',
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de l\'activation de la taxe', 500);
        }
    }

    public function deactivate(Taxe $taxe)
    {
        try {
            //code...
            $taxe->update(['is_active' => false]);
            return successResponse(
                new TaxeResource($taxe),
                'Taxe désactivée avec succès',
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la désactivation de la taxe', 500);
        }
    }
}
