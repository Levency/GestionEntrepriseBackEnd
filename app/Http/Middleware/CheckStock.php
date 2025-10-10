<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si des produits sont en rupture de stock
        $lowStockProducts = \App\Models\Produit::whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->where('is_active', true)
            ->count();
        
        // Ajouter l'info dans les headers de la réponse
        $response = $next($request);
        $response->headers->set('X-Low-Stock-Count', $lowStockProducts);
        
        return $response;
    }
}
