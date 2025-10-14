<?php

namespace App\Http\Controllers\api\stock;

use App\Http\Requests\MovementRequest;
use Illuminate\Http\Request;
use App\Models\StockMouvement;
use App\Http\Controllers\Controller;


class StockMovementController extends Controller
{
    /**
     * Liste tous les mouvements de stock
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type');
        $productId = $request->get('product_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $query = StockMouvement::with(['product', 'user']);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $movements = $query->latest();
        
        return successResponse(
            $movements->paginate($perPage)
        );
    }

    /**
     * Créer un nouveau mouvement de stock
     */
    public function store(MovementRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        try {
            //code...
            $movement = StockMouvement::create($data);
            return successResponse(
                $movement,
                'Mouvement de stock créé avec succès',
                201
            );

        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la création du mouvement de stock', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(MovementRequest $request, StockMouvement $movement)
    {
        
        $data = $request->validated();
        
        try {
            //code...
            $movement->update($data);
            
            return successResponse(
                $movement,
                'Mouvement de stock mis à jour avec succès'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la mise à jour du mouvement de stock', 500);
        }
    }

    /**
     * Supprimer un mouvement de stock
     */
    public function destroy(StockMouvement $movement)
    {
        try {
            //code...
            $movement->delete();
            return successResponse(
                null,
                'Mouvement de stock supprimé avec succès'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la suppression du mouvement de stock', 500);
        }
    }

    /**
     * Mouvements par produit
     */
    public function byProduct($productId)
    {
        $movements = StockMouvement::where('product_id', $productId)
            ->with('user')
            ->latest()
            ->paginate(20);
        
        return successResponse(
            $movements
        );
    }

    /**
     * Rapport des mouvements de stock
     */
    public function report(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());
        
        $movements = StockMouvement::whereBetween('created_at', [$startDate, $endDate])
            ->with('product')
            ->get();
        
        $summary = [
            'total_in' => $movements->where('type', 'in')->sum('quantity'),
            'total_out' => $movements->where('type', 'out')->sum('quantity'),
            'total_adjustments' => $movements->where('type', 'adjustment')->count(),
            'movements_by_type' => [
                'in' => $movements->where('type', 'in')->count(),
                'out' => $movements->where('type', 'out')->count(),
                'adjustment' => $movements->where('type', 'adjustment')->count(),
            ],
            'movements_by_product' => $movements->groupBy('product_id')
                ->map(function($productMovements) {
                    return [
                        'product' => $productMovements->first()->product->name,
                        'total_movements' => $productMovements->count(),
                        'quantity_in' => $productMovements->where('type', 'in')->sum('quantity'),
                        'quantity_out' => $productMovements->where('type', 'out')->sum('quantity'),
                    ];
                })->values()
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'movements' => $movements
            ]
        ]);
    }
}

