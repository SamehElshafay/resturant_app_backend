<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Records a double-entry transaction.
     *
     * @param int $branchId
     * @param int $debitAccountId
     * @param int $creditAccountId
     * @param float $amount
     * @param string|null $description
     * @param int|null $userId
     * @param array $extraData [order_id, voucher_id]
     * @return void
     */
    public function recordTransaction(
        int $branchId,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        ?string $description = null,
        ?int $userId = null,
        array $extraData = []
    ) {
        DB::transaction(function () use ($branchId, $debitAccountId, $creditAccountId, $amount, $description, $userId, $extraData) {
            // 1. Debit Entry
            Ledger::create([
                'branch_id' => $branchId,
                'account_id' => $debitAccountId,
                'user_id' => $userId,
                'order_id' => $extraData['order_id'] ?? null,
                'voucher_id' => $extraData['voucher_id'] ?? null,
                'debit' => $amount,
                'credit' => 0,
                'description' => $description,
            ]);

            // 2. Credit Entry
            Ledger::create([
                'branch_id' => $branchId,
                'account_id' => $creditAccountId,
                'user_id' => $userId,
                'order_id' => $extraData['order_id'] ?? null,
                'voucher_id' => $extraData['voucher_id'] ?? null,
                'debit' => 0,
                'credit' => $amount,
                'description' => $description,
            ]);
        });
    }

    /**
     * Get account balance.
     */
    public function getBalance(int $accountId)
    {
        return Ledger::where('account_id', $accountId)
            ->select(DB::raw('SUM(debit) - SUM(credit) as balance'))
            ->first()
            ->balance ?? 0;
    }
}
