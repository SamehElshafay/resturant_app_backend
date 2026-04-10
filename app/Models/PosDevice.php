<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AutoCastTypes;

class PosDevice extends Model
{
    use AutoCastTypes;
    protected $fillable = ['branch_id', 'name', 'connection_type', 'address', 'account_id', 'account_code'];

    protected $casts = [
        'id' => 'integer',
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
