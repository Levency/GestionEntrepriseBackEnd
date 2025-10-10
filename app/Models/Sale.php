<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_number',
        'customer_name',
        'customer_phone',
        'discount',
        'total',
        'paid_amount',
        'change_amount',
        'payement_method',
        'status',
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

}
