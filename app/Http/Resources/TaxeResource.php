<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxeResource extends JsonResource
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
            'name' => $this->name,
            'rate' => $this->rate,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'statistics' => [
                'total_taxes_count' => \App\Models\Taxe::count(),
                'average_tax_rate' => \App\Models\Taxe::avg('rate'),
                'max_tax_rate' => \App\Models\Taxe::max('rate'),
                'min_tax_rate' => \App\Models\Taxe::min('rate'),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
