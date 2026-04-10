<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $fillable = [
        'branch_id',
        'account_id',
        'user_id',
        'order_id',
        'voucher_id',
        'debit',
        'credit',
        'description'
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'account_id' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'voucher_id' => 'integer',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];
}
