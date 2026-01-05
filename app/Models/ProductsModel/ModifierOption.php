<?php

namespace App\Models\ProductsModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierOption extends Model {
    use HasFactory;

    protected $table = 'modifier_options';

    protected $fillable = [
        'name',
        'modifier_id',
        'is_default',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'modifier_id' => 'integer',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function modifier(){
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }
}