<?php

namespace App\Models\CustomerModel;

use App\Models\CustomerModel\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSmsLog extends Model
{
    use HasFactory;

    protected $table = 'customer_sms_log';

    protected $fillable = [
        'customer_id',
        'type',
        'verified_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'verified_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}