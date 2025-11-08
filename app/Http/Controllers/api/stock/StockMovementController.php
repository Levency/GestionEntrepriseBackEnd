<?php

namespace App\Http\Controllers\api\stock;

use App\Http\Requests\MovementRequest;
use Illuminate\Http\Request;
use App\Models\StockMouvement;
use App\Http\Controllers\Controller;
use App\Models\Produit;


class StockMovementController extends Controller
{
    /**
     * Liste tous les mouvements de stock
     */
   /**
     * Liste tous les mouvements de stock
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type');
        $productId = $request->get('product_id');
        
        $query = StockMouvement::with('product');
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        
        $movements = $query->latest()->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $movements->map(function($movement) {
                return [
                    'id' => $movement->id,
                    'product_id' => $movement->produit_id,
                    'product_name' => $movement->product->name,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'reason' => $movement->reason,
                    'created_at' => $movement->created_at,
                ];
            })
        ]);
    }

        /**
     * Créer un nouveau mouvement avec contraintes
     */
    public function store(Request $request)
{
    $request->validate([
        'produit_id' => 'required|exists:produits,id',
        'type' => 'required|in:in,out,adjustment',
        'quantity' => 'required|integer|min:1',
        'reason' => 'nullable|string|max:255',
    ]);

    $product = Produit::find($request->produit_id);

    if ($request->type === 'out' && $product->stock_quantity < $request->quantity) {
        return response()->json([
            'success' => false,
            'message' => "Stock insuffisant. Stock actuel: {$product->stock_quantity}, demandé: {$request->quantity}"
        ], 400);
    }

    try {
        \DB::beginTransaction();

        $movement = StockMouvement::create([
            'produit_id' => $request->produit_id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'reason' => $request->reason,
        ]);

        // Mise à jour du stock
        if ($request->type === 'in') {
            $product->stock_quantity += $request->quantity;
        } elseif ($request->type === 'out') {
            $product->stock_quantity -= $request->quantity;
        }

        $product->save();
        \DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Mouvement de stock créé avec succès',
            'data' => [
                'id' => $movement->id,
                'produit_id' => $movement->produit_id,
                'product_name' => $product->name,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'reason' => $movement->reason,
                'created_at' => $movement->created_at,
                'new_stock' => $product->stock_quantity,
            ]
        ], 201);

    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création: ' . $e->getMessage()
        ], 500);
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
     * Statistiques
     */
    public function stats()
    {
        $totalIn = StockMouvement::where('type', 'in')->sum('quantity');
        $totalOut = StockMouvement::where('type', 'out')->sum('quantity');
        $totalMovements = StockMouvement::count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'total_movements' => $totalMovements,
                'net_movement' => $totalIn - $totalOut,
            ]
        ]);
    }
}

