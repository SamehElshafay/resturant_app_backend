<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'branch_id',
        'status',
        'payment_status',
        'total_amount',
        'paid_amount',
        'invoice_date',
        'notes',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
