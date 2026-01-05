<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverSafe extends Model
{
    use HasFactory;

    protected $table = 'driver_safe';

    protected $fillable = [
        'branch_creditor_id',
        'driver_debtor_id',
        'amount',
        'funds_delivered',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'branch_creditor_id' => 'integer',
        'driver_debtor_id' => 'integer',
        'amount' => 'float',
        'funds_delivered' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع Branch (الجهة الدائنة)
    public function branchCreditor()
    {
        return $this->belongsTo(Branch::class, 'branch_creditor_id');
    }

    // العلاقة مع Driver (المدين)
    public function driverDebtor()
    {
        return $this->belongsTo(Driver::class, 'driver_debtor_id');
    }
}