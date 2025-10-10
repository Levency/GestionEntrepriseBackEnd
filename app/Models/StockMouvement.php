<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMouvement extends Model
{
    use HasFactory;
    protected $fillable = [
        'produit_id',
        'type',
        'quantity',
        'reason',
    ];

    public function product()
    {
        return $this->belongsTo(Produit::class, 'product_id');
    }
}
