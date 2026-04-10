<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryBranchPrinter extends Model
{
    protected $fillable = ['category_id', 'branch_id', 'printer_ip', 'printer_connection_type'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
