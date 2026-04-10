<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosDevice extends Model
{
    protected $fillable = ['branch_id', 'name', 'connection_type', 'address', 'account_id', 'account_code'];

    protected $casts = [
        'branch_id' => 'integer',
        'account_id' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
