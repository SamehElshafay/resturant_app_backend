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
}
