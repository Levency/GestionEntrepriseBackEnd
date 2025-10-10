<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
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
            'invoice_number' => 'required|string|max:50|unique:sales,invoice_number,' . $this->route('sale'),
            'customer_name' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:15',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,mobile_money,bank_transfer,credit',
            'status' => 'nullable|in:completed,pending,cancelled,refunded',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.required' => 'Le numéro de facture est obligatoire.',
            'invoice_number.unique' => 'Le numéro de facture doit être unique.',
            'customer_name.string' => 'Le nom du client doit être une chaîne de caractères.',
            'customer_phone.string' => 'Le téléphone du client doit être une chaîne de caractères.',
            'discount.numeric' => 'La remise doit être un nombre.',
            'total.required' => 'Le total est obligatoire.',
            'total.numeric' => 'Le total doit être un nombre.',
            'paid_amount.numeric' => "Le montant payé doit être un nombre.",
            'change_amount.numeric' => "Le montant du changement doit être un nombre.",
            'payment_method.in' => "La méthode de paiement doit être l'une des suivantes : cash, card, mobile_money, bank_transfer, credit.",
            'status.in' => "Le statut doit être l'un des suivants : completed, pending, cancelled, refunded.",
        ];
    }
}
