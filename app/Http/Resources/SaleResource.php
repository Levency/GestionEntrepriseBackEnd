<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'discount' => $this->discount,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            "Items" =>  SaleItemResource::collection($this->saleItems),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
