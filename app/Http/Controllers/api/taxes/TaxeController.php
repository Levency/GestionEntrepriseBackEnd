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
        return response()->json([
            'status' => 'success',
            'message' => 'Taxes récupérées avec succès',
            'data' => TaxeResource::collection(Taxe::all()),
        ]);
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
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }
        try {
            $taxe = Taxe::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Taxe créée avec succès',
                'data' => new TaxeResource($taxe),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la taxe',
            ], 500);
        }
    }
    public function show($id)
    {
        $taxe = Taxe::find($id);
        if (!$taxe) return response()->json(['status' => 'error', 'message' => 'Taxe non trouvée'], 404);
        return response()->json([
            'status' => 'success',
            'message' => 'Taxe récupérée avec succès',
            'data' => new TaxeResource($taxe),
        ]);
    }
    public function update(Request $request, $id)
    {
        $taxe = Taxe::find($id);
        if (!$taxe) return response()->json(['status' => 'error', 'message' => 'Taxe non trouvée'], 404);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:taxes,name,' . $id,
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }
        try {
            $taxe->update($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Taxe mise à jour avec succès',
                'data' => new TaxeResource($taxe),
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour de la taxe',
            ], 500);
        }
    }
    public function destroy($id)
    {
        $taxe = Taxe::find($id);
        if (!$taxe) return response()->json(['status' => 'error', 'message' => 'Taxe non trouvée'], 404);
        try {
            $taxe->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Taxe supprimée avec succès',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la taxe',
            ], 500);
        }
    }
    public function activate($id)
    {
        $taxe = Taxe::find($id);
        if (!$taxe) return response()->json(['status' => 'error', 'message' => 'Taxe non trouvée'], 404);
        try {
            $taxe->update(['is_active' => true]);
            return response()->json([
                'status' => 'success',
                'message' => 'Taxe activée avec succès',
                'data' => new TaxeResource($taxe),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'activation de la taxe',
            ], 500);
        }
    }
    public function deactivate($id)
    {
        $taxe = Taxe::find($id);
        if (!$taxe) return response()->json(['status' => 'error', 'message' => 'Taxe non trouvée'], 404);
        try {
            $taxe->update(['is_active' => false]);
            return response()->json([
                'status' => 'success',
                'message' => 'Taxe désactivée avec succès',
                'data' => new TaxeResource($taxe),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la désactivation de la taxe',
            ], 500);
        }
    }
}