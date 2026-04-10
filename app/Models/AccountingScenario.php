<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingScenario extends Model
{
    protected $fillable = [
        'name',
        'event_key',
        'trigger_type',
        'trigger_value',
        'is_active',
    ];

    public function steps()
    {
        return $this->hasMany(AccountingScenarioStep::class, 'scenario_id')->orderBy('priority');
    }
}
