<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryBranchPrinter extends Model
{
    protected $fillable = ['category_id', 'branch_id', 'printer_ip', 'printer_connection_type'];

    protected $casts = [
        'category_id' => 'integer',
        'branch_id' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
