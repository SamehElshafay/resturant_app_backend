<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchProduct extends Model
{
    protected $fillable = ['branch_id', 'product_id', 'price', 'stock_quantity'];

    protected $casts = [
        'branch_id' => 'integer',
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'stock_quantity' => 'decimal:3',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
