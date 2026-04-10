<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'transaction_group_id',
        'debit_account_code',
        'credit_account_code',
        'debit',
        'credit',
        'reference_type',
        'reference_id',
        'description',
        'payload',
    ];

    protected $casts = [
        'id' => 'integer',
        'payload' => 'array',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Get the parent account code for a given code (e.g. '101-005' -> '101')
     */
    public static function getParentCode(string $code): ?string
    {
        $parts = explode('-', $code);
        if (count($parts) < 2) {
            return null; // no parent
        }
        array_pop($parts);
        return implode('-', $parts);
    }

    /**
     * Get the account record for a given code.
     */
    public static function accountByCode(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }
}
