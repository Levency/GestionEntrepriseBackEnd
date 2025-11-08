<?php

namespace App\Http\Controllers\api\stock;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResources;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {
        return successResponse(
            CategoryResource::collection(Category::all()->loadCount('products'))
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        try {
            //code...
            $category = Category::create($request->all());
            return successResponse(
                new CategoryResource($category),
                'Catégorie créée avec succès',
                201
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la création de la catégorie', 500);
        }

        
    }

    public function show(Category $category)
    {
        return successResponse(
            new CategoryResource($category)
        );
    }

    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);
        try {
            //code...
            $category->update($request->all());
            return successResponse(
                new CategoryResource($category),
                'Catégorie mise à jour avec succès'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la mise à jour de la catégorie', 500);
        }
    }

    public function destroy(Category $category)
    {
      try {
            //code...
            $category->delete();
            //throw $th;
            return successResponse(
                null,
                'Catégorie supprimée avec succès'
            );
        } catch (\Throwable $th) {
            //throw $th;
            return errorResponse('Erreur lors de la dissociation des produits de la catégorie', 500);
        } 
    }

    public function products(Category $category)
    {
        
        $products = $category->products()->paginate(15);
        return successResponse(
            ProductResources::collection($products)
        );
    }
}

