<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBilingualName;

class Category extends Model
{
    use HasBilingualName;

    protected $fillable = ['name', 'name_ar', 'name_en', 'image', 'printer_ip', 'printer_connection_type'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function branchPrinters()
    {
        return $this->hasMany(CategoryBranchPrinter::class);
    }
}
