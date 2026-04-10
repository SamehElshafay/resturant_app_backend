<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = ['purchase_invoice_id', 'ingredient_id', 'quantity', 'remaining_quantity', 'unit_price', 'total_price'];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
