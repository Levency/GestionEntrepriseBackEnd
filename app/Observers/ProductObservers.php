<?php

namespace App\Observers;

use App\Models\Produit;
use Illuminate\Support\Facades\Log;

class ProductObservers
{
     public function created(Produit $produit)
    {
        Log::info('Nouveau produit créé', [
            'produit_id' => $produit->id,
            'name' => $produit->name,
            'stock' => $produit->stock_quantity
        ]);
    }

    public function updated(produit $produit)
    {
        // Vérifier si le stock a changé
        if ($produit->isDirty('stock_quantity')) {
            $original = $produit->getOriginal('stock_quantity');
            $new = $produit->stock_quantity;
            
            Log::info('Stock mis à jour', [
                'produit_id' => $produit->id,
                'name' => $produit->name,
                'old_stock' => $original,
                'new_stock' => $new
            ]);
            
            // Alerte stock bas
            if ($produit->isLowStock()) {
                Log::warning('Alerte stock bas', [
                    'produit_id' => $produit->id,
                    'name' => $produit->name,
                    'stock' => $produit->stock_quantity,
                    'min_level' => $produit->min_stock_level
                ]);
            }
        }
    }

    public function deleted(Produit $produit)
    {
        Log::info('Produit supprimé', [
            'produit_id' => $produit->id,
            'name' => $produit->name
        ]);
    }
}
