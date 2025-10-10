<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovementRequest extends FormRequest
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
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'produit_id.required' => 'Le champ produit est obligatoire.',
            'produit_id.exists' => 'Le produit sélectionné est invalide.',
            'type.required' => 'Le champ type est obligatoire.',
            'type.in' => 'Le type doit être "in" ou "out".',
            'quantity.required' => 'Le champ quantité est obligatoire.',
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité doit être au moins de 1.',
            'reason.string' => 'La raison doit être une chaîne de caractères.',
            'reason.max' => 'La raison ne peut pas dépasser 255 caractères.',
        ];
    }
}
