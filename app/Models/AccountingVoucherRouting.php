<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingVoucherRouting extends Model
{
    protected $fillable = [
        'entity_type',
        'voucher_type',
        'parent_account_code',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
