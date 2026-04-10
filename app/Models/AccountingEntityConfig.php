<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingEntityConfig extends Model
{
    protected $fillable = [
        'entity_type',
        'parent_account_code',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
