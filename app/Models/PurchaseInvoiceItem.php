<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = ['purchase_invoice_id', 'ingredient_id', 'quantity', 'remaining_quantity', 'unit_price', 'total_price'];

    protected $casts = [
        'purchase_invoice_id' => 'integer',
        'ingredient_id' => 'integer',
        'quantity' => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
