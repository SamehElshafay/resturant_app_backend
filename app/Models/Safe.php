<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Safe extends Model
{
    use HasFactory;

    protected $table = 'safe';

    protected $fillable = [
        'safe_name',
        'creditor_id',
        'debtor_id',
        'amount',
        'creditor_agent_type_id',
        'debtor_agent_type_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'creditor_id' => 'integer',
        'debtor_id' => 'integer',
        'amount' => 'float',
        'creditor_agent_type_id' => 'integer',
        'debtor_agent_type_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creditor()
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }

    public function debtor()
    {
        return $this->belongsTo(User::class, 'debtor_id');
    }

    public function creditorAgentType()
    {
        return $this->belongsTo(AgentType::class, 'creditor_agent_type_id');
    }

    public function debtorAgentType()
    {
        return $this->belongsTo(AgentType::class, 'debtor_agent_type_id');
    }
}