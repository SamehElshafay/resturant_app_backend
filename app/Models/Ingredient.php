<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'unit',
        'cost_price',
        'stock_quantity',
        'min_stock_level'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'decimal:3',
        'min_stock_level' => 'decimal:3',
    ];

    public function recipes()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function deductStock($quantity)
    {
        // 1. Deduct from FIFO Layers (Approved Purchase Invoices)
        $remainingToDeduct = $quantity;
        $invoiceItems = PurchaseInvoiceItem::where('ingredient_id', $this->id)
            ->where('remaining_quantity', '>', 0)
            ->whereHas('purchaseInvoice', function ($q) {
                $q->where('status', 'approved');
            })
            ->orderBy('created_at', 'asc') // FIFO
            ->get();

        foreach ($invoiceItems as $item) {
            if ($remainingToDeduct <= 0)
                break;

            if ($item->remaining_quantity >= $remainingToDeduct) {
                $item->remaining_quantity -= $remainingToDeduct;
                $item->save();
                $remainingToDeduct = 0;
            } else {
                $remainingToDeduct -= $item->remaining_quantity;
                $item->remaining_quantity = 0;
                $item->save();
            }
        }

        // 2. Update central stock quantity
        $this->stock_quantity -= $quantity;
        $this->save();

        return true;
    }

    /**
     * Recalculate Weighted Average Cost based on available FIFO layers (remaining invoices)
     */
    public function recalculateCost()
    {
        // Get all approved invoice items with remaining quantity for this ingredient
        $items = PurchaseInvoiceItem::where('ingredient_id', $this->id)
            ->where('remaining_quantity', '>', 0)
            ->whereHas('purchaseInvoice', function ($query) {
                $query->where('status', 'approved'); // Only approved invoices count
            })
            ->get();

        $totalValue = 0;
        $totalQuantity = 0;

        foreach ($items as $item) {
            $totalValue += ($item->remaining_quantity * $item->unit_price);
            $totalQuantity += $item->remaining_quantity;
        }

        if ($totalQuantity > 0) {
            $this->cost_price = $totalValue / $totalQuantity;
        } else {
            // No stock left, keep last known cost or set to 0? Usually keep last known cost to avoid 0 cost recipes.
            // But if stock is 0, cost is technically irrelevant until next purchase. Let's keep existing.
        }

        $this->stock_quantity = $totalQuantity; // Ensure stock matches invoice residuals
        $this->save();

        return $this->cost_price;
    }
}
