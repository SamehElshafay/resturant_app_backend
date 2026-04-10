<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingScenarioStep extends Model
{
    protected $fillable = [
        'scenario_id',
        'parent_id',
        'description',
        'debit_account_pattern',
        'credit_account_pattern',
        'debit_amount_formula',
        'credit_amount_formula',
        'amount_formula',
        'condition_expression',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'id' => 'integer',
        'scenario_id' => 'integer',
        'parent_id' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scenario()
    {
        return $this->belongsTo(AccountingScenario::class);
    }
}
