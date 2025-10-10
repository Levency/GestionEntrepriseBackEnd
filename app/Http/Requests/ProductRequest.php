<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'min_stock_level' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est requis.',
            'name.string' => 'Le nom du produit doit être une chaîne de caractères.',
            'name.max' => 'Le nom du produit ne doit pas dépasser 255 caractères.',
            'purchase_price.required' => 'Le prix d\'achat est requis.',
            'purchase_price.numeric' => 'Le prix d\'achat doit être un nombre.',
            'purchase_price.min' => 'Le prix d\'achat doit être au moins :min.',
            'selling_price.required' => 'Le prix de vente est requis.',
            'selling_price.numeric' => 'Le prix de vente doit être un nombre.',
            'stock_quantity.required' => 'La quantité en stock est requise.',
            'stock_quantity.integer' => 'La quantité en stock doit être un entier.',
            'stock_quantity.min' => 'La quantité en stock doit être au moins :min.',
            'category_id.required' => 'La catégorie est requise.',
            'category_id.exists' => 'La catégorie sélectionnée est invalide.',
        ];
    }
}
