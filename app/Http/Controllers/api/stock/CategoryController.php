<?php

namespace App\Http\Controllers\api\stock;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        
        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $category
        ], 201);
    }

    public function show($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($category)
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès',
            'data' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
        
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer: des produits sont associés à cette catégorie'
            ], 400);
        }
        
        $category->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    public function products($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
        
        $products = $category->products()->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}

