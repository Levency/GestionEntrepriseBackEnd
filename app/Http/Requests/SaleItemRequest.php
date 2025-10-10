<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleItemRequest extends FormRequest
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
            'produit_id' => 'required|exists:produits,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'sale_id' => 'required|exists:sales,id',
        ];
    }

    public function messages(): array
    {
        return [
            'produit_id.required' => 'Le produit est obligatoire.',
            'produit_id.exists' => 'Le produit sélectionné est invalide.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité doit être au moins de 1.',
            'unit_price.required' => "Le prix unitaire est obligatoire.",
            'unit_price.numeric' => "Le prix unitaire doit être un nombre.",
            'unit_price.min' => "Le prix unitaire doit être au moins de 0.",
            'total_price.required' => "Le prix total est obligatoire.",
            'total_price.numeric' => "Le prix total doit être un nombre.",
            'total_price.min' => "Le prix total doit être au moins de 0.",
            'sale_id.required' => 'La vente est obligatoire.',
            'sale_id.exists' => 'La vente sélectionnée est invalide.',
        ];
    }
}
