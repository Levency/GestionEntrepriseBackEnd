<?php

namespace App\Http\Controllers\api\sales;

use App\Models\Sale;
use App\Models\Produit;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use App\Models\StockMouvement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        
        $query = Sale::with( 'saleItems');
        
    
        
        $sales = $query->latest('created_at')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => SaleResource::collection($sales)
        ]);
    }

    /**
     * Créer une nouvelle vente
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:15',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.produit_id' => 'required|exists:produits,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,mobile_money,bank_transfer,credit',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Vérifier la disponibilité du stock
            foreach ($request->items as $item) {
                $product = Produit::find($item['produit_id']);
                if ($product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}. Disponible: {$product->stock_quantity}"
                    ], 400);
                }
            }
            
            // Calculer les totaux
            $subtotal = 0;
            $itemsData = [];
            
            foreach ($request->items as $item) {
                $itemSubtotal = $item['quantity'] * $product->selling_price;
                $discount = $item['discount'] ?? 0;
                $itemSubtotal -= $discount;
                
                $subtotal += $itemSubtotal;
                $itemsData[] = [
                    'produit_id' => $item['produit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'discount' => $discount,
                    'subtotal' => $itemSubtotal
                ];
            }
            
            $discount = $request->discount ?? 0;
            $total = $subtotal - $discount;
            $paidAmount = $request->paid_amount;
            $changeAmount = max(0, $paidAmount - $total);
            
            // Vérifier le paiement
            if ($request->payment_method !== 'credit' && $paidAmount < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Montant payé insuffisant'
                ], 400);
            }
            
            // Créer la vente
            $sale = Sale::create([
                'invoice_number' => 'INV-' . date('YmdHis') . rand(100, 999),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',
            ]);
            
            // Ajouter les items et mettre à jour le stock
            foreach ($itemsData as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'produit_id' => $itemData['produit_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal']
                ]);
                
                // Mettre à jour le stock
                $product = Produit::find($itemData['produit_id']);
            
                $previousStock = $product->stock_quantity;
                $newStock = $previousStock - $itemData['quantity'];
                
                $product->update(['stock_quantity' => $newStock]);
                
                // Enregistrer le mouvement de stock
                StockMouvement::create([
                    'produit_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reference' => 'sale_' . $sale->id,
                    'reason' => 'Vente - Facture: ' . $sale->invoice_number
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vente enregistrée avec succès',
                'data' => $sale->load('saleItems')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une vente spécifique
     */
    public function show($id)
    {
        $sale = Sale::find($id);
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => SaleResource::make($sale->load('saleItems'))
        ]);
    }

        /**
        * Mettre à jour une vente (limité aux informations non financières)
        */
    public function update(Request $request, $id)
    {
        $sale = Sale::find($id);   
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:15',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.produit_id' => 'required|exists:produits,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,mobile_money,bank_transfer,credit',
            'paid_amount' => 'required|numeric|min:0',
        ]);
        DB::beginTransaction();

        try {
            // Vérifier la disponibilité du stock
            foreach ($request->items as $item) {
                $product = Produit::find($item['produit_id']);
                if ($product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}. Disponible: {$product->stock_quantity}"
                    ], 400);
                }
            }
            
            // Calculer les totaux
            $subtotal = 0;
            $itemsData = [];
            
            foreach ($request->items as $item) {
                $itemSubtotal = $item['quantity'] * $product->selling_price;
                $discount = $item['discount'] ?? 0;
                $itemSubtotal -= $discount;
                
                $subtotal += $itemSubtotal;
                $itemsData[] = [
                    'produit_id' => $item['produit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'discount' => $discount,
                    'subtotal' => $itemSubtotal
                ];
            }
            
            $discount = $request->discount ?? 0;
            $total = $subtotal - $discount;
            $paidAmount = $request->paid_amount;
            $changeAmount = max(0, $paidAmount - $total);
            
            // Vérifier le paiement
            if ($request->payment_method !== 'credit' && $paidAmount < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Montant payé insuffisant'
                ], 400);
            }
            // Créer la vente
            $sale->update([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',
            ]);
            
            // Ajouter les items et mettre à jour le stock
            foreach ($itemsData as $itemData) {
                $sale->saleItems()->update([
                    'produit_id' => $itemData['produit_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal']
                ]);
                
                // Mettre à jour le stock
                $product = Produit::find($itemData['produit_id']);
            
                $previousStock = $product->stock_quantity;
                $newStock = $previousStock - $itemData['quantity'];
                
                $product->update(['stock_quantity' => $newStock]);
                
                $product->stockMouvements()->update([
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'reason' => 'Vente - Facture: ' . $sale->invoice_number
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vente enregistrée avec succès',
                'data' => $sale->load('saleItems')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vente rapide (sans client)
     */
    public function quickSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,mobile_money',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $items = [];
            
            foreach ($request->items as $item) {
                $product = Produit::find($item['product_id']);
                
                if ($product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}"
                    ], 400);
                }
                
                $itemSubtotal = $item['quantity'] * $product->selling_price;
                $subtotal += $itemSubtotal;
                
                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'subtotal' => $itemSubtotal
                ];
            }
            
            if ($request->paid_amount < $subtotal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Montant insuffisant'
                ], 400);
            }
            
            $sale = Sale::create([
                'invoice_number' => 'INV-' . date('YmdHis') . rand(100, 999),
                'user_id' => auth()->id(),
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'paid_amount' => $request->paid_amount,
                'change_amount' => $request->paid_amount - $subtotal,
                'payment_method' => $request->payment_method,
                'status' => 'completed'
            ]);
            
            foreach ($items as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal']
                ]);
                
                $product = Produit::find($itemData['product_id']);
                $previousStock = $product->stock_quantity;
                $newStock = $previousStock - $itemData['quantity'];
                
                $product->update(['stock_quantity' => $newStock]);
                
                StockMouvement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reference' => 'sale_' . $sale->id,
                    'reason' => 'Vente rapide - ' . $sale->invoice_number
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vente enregistrée avec succès',
                'data' => $sale->load('items.product')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rembourser une vente
     */
    public function refund($id)
    {
        $sale = Sale::with('saleItems')->find($id);
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        
        if ($sale->status === 'refunded') {
            return response()->json([
                'success' => false,
                'message' => 'Cette vente a déjà été remboursée'
            ], 400);
        }
        DB::beginTransaction();
        try {
            // Remettre les produits en stock
            foreach ($sale->saleItems as $item) {
                $product = Produit::find($item->produit_id);
                $previousStock = $product->stock_quantity;
                $newStock = $previousStock + $item->quantity;
                
                $product->update(['stock_quantity' => $newStock]);
                
                StockMouvement::create([
                    'produit_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reason' => 'Remboursement - ' . $sale->invoice_number
                ]);
            }
            
            $sale->update(['status' => 'refunded']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vente remboursée avec succès',
                'data' => $sale->load('saleItems')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une vente
     */
    public function cancel($id)
    {
        $sale = Sale::find($id);
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        
        if ($sale->status === 'cancelled' || $sale->status === 'refunded') {
            return response()->json([
                'success' => false,
                'message' => 'Cette vente ne peut pas être annulée'
            ], 400);
        }
        
        $sale->update(['status' => 'cancelled']);
        
        return response()->json([
            'success' => true,
            'message' => 'Vente annulée avec succès',
            'data' => $sale->load('saleItems')
            
        ]);
    }

    /**
     * Suspendre une vente
     */
    public function pending($id)
    {
        $sale = Sale::find($id);
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        
        if ($sale->status === 'cancelled' || $sale->status === 'refunded') {
            return response()->json([
                'success' => false,
                'message' => 'Cette vente ne peut pas être suspendu'
            ], 400);
        }
        
        $sale->update(['status' => 'Pending']);
        
        return response()->json([
            'success' => true,
            'message' => 'Vente suspendu avec succès',
            'data' => $sale->load('saleItems')
        ]);
    }

    /**
     * Résumé des ventes du jour
     */
    public function todaySummary()
    {
        $today = now()->startOfDay();
        
        $sales = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->get();
        
        $summary = [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'total_paid' => $sales->sum('paid_amount'),
            'total_discount' => $sales->sum('discount'),
            'by_payment_method' => [
                'cash' => $sales->where('payment_method', 'cash')->sum('total'),
                'card' => $sales->where('payment_method', 'card')->sum('total'),
                'mobile_money' => $sales->where('payment_method', 'mobile_money')->sum('total'),
                'bank_transfer' => $sales->where('payment_method', 'bank_transfer')->sum('total'),
                'credit' => $sales->where('payment_method', 'credit')->sum('total'),
            ],
            'items_sold' => SaleItem::whereIn('sale_id', $sales->pluck('id'))->sum('quantity'),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Imprimer une facture
     */
    public function print($id)
    {
        $sale = Sale::with(['items.product', 'customer', 'user'])->find($id);
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        
        // Générer le HTML de la facture
        $html = view('invoices.print', compact('sale'))->render();
        
        return response()->json([
            'success' => true,
            'data' => [
                'html' => $html,
                'sale' => $sale
            ]
        ]);
    }

    /**
     * Supprimer une vente
     */
    public function destroy($id)
    {
        $sale = Sale::find($id);
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente non trouvée'
            ], 404);
        }
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une vente complétée. Utilisez le remboursement.'
            ], 400);
        }
        
        $sale->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Vente supprimée avec succès'
        ]);
    }
}

