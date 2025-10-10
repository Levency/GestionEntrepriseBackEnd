<?php

namespace App\Jobs;

use App\Models\Produit;
use Illuminate\Bus\Queueable;
use App\Models\StockMouvement;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;
    protected $quantity;
    protected $type;
    protected $userId;
    protected $reason;

    public function __construct($productId, $quantity, $type, $userId, $reason)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->type = $type;
        $this->userId = $userId;
        $this->reason = $reason;
    }

    public function handle()
    {
        $product = Produit::find($this->productId);
        
        if (!$product) {
            return;
        }
        
        $previousStock = $product->stock_quantity;
        
        switch ($this->type) {
            case 'in':
                $newStock = $previousStock + $this->quantity;
                break;
            case 'out':
                $newStock = max(0, $previousStock - $this->quantity);
                break;
            default:
                $newStock = $this->quantity;
        }
        
        $product->update(['stock_quantity' => $newStock]);
        
        StockMouvement::create([
            'product_id' => $product->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reason' => $this->reason
        ]);
    }

}
