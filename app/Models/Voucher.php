<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'voucher_type',
        'expense_id',
        'voucher_expense_type_id',
        'expense_type',
        'amount',
        'cash_amount',
        'bank_amount',
        'account_code',
        'entity_type',
        'recipient_account_code',
        'recipient_entity_type',
        'treasury_account_code',
        'bank_account_code',
        'note',
        'reference_number',
        'status',
        'operation_code',
        'transfer_status',
        'created_by',
        'posted_by',
        'posted_at',
        'is_sttel',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'bank_amount' => 'decimal:2',
        'is_sttel' => 'boolean',
        'posted_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reference_id')
            ->where('reference_type', 'voucher');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }
    public function isPosted(): bool
    {
        return $this->status === 'POSTED';
    }
    public function isReceipt(): bool
    {
        return $this->voucher_type === 'RECEIPT';
    }
    public function isPayment(): bool
    {
        return $this->voucher_type === 'PAYMENT';
    }
    public function isTransfer(): bool
    {
        return $this->voucher_type === 'TRANSFER';
    }
}
