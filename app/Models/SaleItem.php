<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'sale_id',
        'produit_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function sales()
    {
        return $this->belongsTo(Sale::class);
    }
}
