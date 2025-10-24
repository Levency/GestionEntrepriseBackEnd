<?php

namespace App\Models;

use App\Observers\ProductObservers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produit extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'category_id',
        'purchase_price',
        'selling_price',
        'min_stock_level',  
        'stock_quantity',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMouvements()
    {
        return $this->hasMany(StockMouvement::class);
    }
    public function saleItems()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock_quantity == 0) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function boot()
    {
        Produit::observe(ProductObservers::class);
    }
}
