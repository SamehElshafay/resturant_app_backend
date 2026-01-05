<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'performed_by',
        'performed_at',
        'old_data',
        'new_data',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'performed_at' => 'datetime',
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}