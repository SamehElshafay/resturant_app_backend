<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AutoCastTypes;

class BranchProduct extends Model
{
    use AutoCastTypes;
    protected $fillable = ['branch_id', 'product_id', 'price', 'stock_quantity'];

    protected $casts = [
        'id' => 'integer',
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
