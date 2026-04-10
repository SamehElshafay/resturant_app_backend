<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBilingualName;

class Supplier extends Model
{
    use HasBilingualName;

    protected $fillable = ['name', 'name_ar', 'name_en', 'email', 'phone', 'address', 'account_id', 'account_code'];

    protected $casts = [
        'account_id' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
