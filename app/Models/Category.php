<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBilingualName;
use App\Traits\AutoCastTypes;

class Category extends Model
{
    use HasBilingualName, AutoCastTypes;

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'image',
        'printer_ip',
        'printer_connection_type'
    ];

    protected $casts = [
        'id' => 'integer',
    ];


    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function branchPrinters()
    {
        return $this->hasMany(CategoryBranchPrinter::class);
    }
}
