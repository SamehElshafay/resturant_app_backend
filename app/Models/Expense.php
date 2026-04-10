<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'branch_id',
        'account_id',
        'source_account_id',
        'type',
        'status',
        'amount',
        'expense_date',
        'name_ar',
        'name_en',
        'description',
        'created_by'
    ];

    protected $casts = [
        'id' => 'integer',
        'expense_date' => 'date',
        'branch_id' => 'integer',
        'account_id' => 'integer',
        'source_account_id' => 'integer',
        'created_by' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function sourceAccount()
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
