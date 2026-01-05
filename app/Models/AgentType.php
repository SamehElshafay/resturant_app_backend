<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentType extends Model
{
    protected $table = 'agent_type';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id'         => 'integer',
        'name'       => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}