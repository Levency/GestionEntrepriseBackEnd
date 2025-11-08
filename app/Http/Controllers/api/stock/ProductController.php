<?php

namespace App\Http\Controllers\api\stock;

use App\Models\Produit;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\StockMouvement;
use Ramsey\Uuid\Rfc4122\NilUuid;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProductResources;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\api\stock\Product;

class ProductController extends Controller
{
    /**
     * Liste tous les produits avec pagination
     */
    public function index(Request $request)
    {
        $products = Produit::with(['category', 'stockMouvements'])
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Produits récupérés avec succès',
            'data' => ProductResources::collection($products),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
        ]);
    }

    /**
     * Créer un nouveau produit
     */
    public function store(ProductRequest $request)
    {
        $request->code = $request->merge(['code' => $this->generateProductCode($request->category_id)]);
        $request->name = $request->name ?? null;
        $request->category_id = $request->category_id ?? 1; // Catégorie par défaut
        $request->min_stock_level = $request->min_stock_level ?? 5;
        $request->stock_quantity = $request->stock_quantity ?? 0;
        $request->purchase_price = $request->purchase_price ?? 0;
        $request->selling_price = $request->selling_price ?? 0;



        DB::beginTransaction();
        try {

            $product = Produit::create($request->all());

            // Créer un mouvement de stock initial
            StockMouvement::create([
                'produit_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock_quantity,
                'reason' => 'Stock initial'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => new ProductResources($product)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateProductCode($categoryId)
    {
        // On peut ajouter un préfixe basé sur la catégorie
        $category = Category::find($categoryId);
        $prefix = $category ? strtoupper(substr($category->name, 0, 3)) : 'PRD';

        // Année courante
        $year = date('Y');

        // Compter le nombre de produits déjà créés cette année
        $count = Produit::whereYear('created_at', $year)->count() + 1;

        // Générer un numéro formaté (ex: 0001, 0002, etc.)
        $number = str_pad($count, 4, '0', STR_PAD_LEFT);

        // Code final du type: CAT-2025-0001
        return "{$prefix}-{$number}";
    }

    /**
     * Afficher un produit spécifique
     */
    public function show(Produit $product)
    {


        return successResponse(
            'Produit récupéré avec succès',
            ProductResources::make($product->load(
                'category',
                'stockMouvements'
            ))
        );
    }

    /**
     * Mettre à jour un produit
     */
    public function update(ProductRequest $request, Produit $product)
    {
        // $product = Produit::find($product);
        $request->code = $request->merge(['code' => $this->generateProductCode($request->category_id)]);
        $request->name = $request->name ?? null;
        $request->category_id = $request->category_id ?? 1; // Catégorie par défaut
        $request->min_stock_level = $request->min_stock_level ?? 5;
        $request->stock_quantity = $request->stock_quantity ?? 0;
        $request->purchase_price = $request->purchase_price ?? 0;
        $request->selling_price = $request->selling_price ?? 0;
        $request->code = $request->code ?? 'PROD-' . strtoupper(uniqid());



        try {

            $product->update($request->all());

            return successResponse(
                'Produit mis à jour avec succès',
                new ProductResources($product)
            );

        } catch (\Exception $e) {
            return errorResponse(
                'Erreur lors de la création: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Supprimer un produit
     */
    public function destroy(Produit $product)
    {
        try {
            $product->delete();

            return successResponse(
                'Produit supprimé avec succès'
            );

        } catch (\Exception $e) {
            return errorResponse(
                'Erreur lors de la suppression: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Ajuster le stock d'un produit
     */
    public function adjustStock(Request $request, Produit $product)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $previousStock = $product->stock_quantity;
            $quantity = $request->quantity;

            switch ($request->type) {
                case 'in':
                    $newStock = $previousStock + $quantity;
                    break;
                case 'out':
                    if ($previousStock < $quantity) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Stock insuffisant'
                        ], 400);
                    }
                    $newStock = $previousStock - $quantity;
                    break;
                case 'adjustment':
                    $newStock = $quantity; // Quantité absolue
                    $quantity = $newStock - $previousStock;
                    break;
            }

            // Mettre à jour le stock
            $product->update(['stock_quantity' => $newStock]);

            // Enregistrer le mouvement
            StockMouvement::create([
                'produit_id' => $product->id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'reason' => $request->reason
            ]);

            DB::commit();

            return successResponse([
                "Ajustement reussie",
                new ProductResources($product)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(
                'Erreur lors de l\'ajustement du stock: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Alertes stock bas
     */
    public function lowStockAlert()
    {
        $produit = Produit::whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->with('category')
            ->get();

        return successResponse(
            'Produits avec stock bas récupérés avec succès',
            ProductResources::collection($produit)
        );
    }

    /**
     * Rechercher des produits
     */
    public function search($query)
    {
        $produit = Produit::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$query}%")
            ->with('category')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produit
        ]);
    }

    /**
     * Trouver par code-barres
     */
    public function findByBarcode($barcode)
    {
        $product = Produit::where('barcode', $barcode)
            ->with('category')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Import en masse
     */
    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit' => 'required|array',
            'produit.*.code' => 'required|unique:produit,code',
            'produit.*.name' => 'required',
            'produit.*.category_id' => 'required|exists:categories,id',
            'produit.*.purchase_price' => 'required|numeric',
            'produit.*.selling_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $imported = 0;
            foreach ($request->produit as $productData) {
                $product = Produit::create($productData);

                // Mouvement de stock initial
                if (isset($productData['stock_quantity'])) {
                    StockMouvement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'quantity' => $productData['stock_quantity'],
                        'previous_stock' => 0,
                        'new_stock' => $productData['stock_quantity'],
                        'reason' => 'Import en masse'
                    ]);
                }
                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$imported} produits importés avec succès"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }
}

