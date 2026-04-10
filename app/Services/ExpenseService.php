<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseService
{
    /**
     * Approve an expense and generate journal entries.
     */
    public function approve(Expense $expense)
    {
        if ($expense->status !== 'pending') {
            throw new \Exception('Only pending expenses can be approved.');
        }

        DB::transaction(function () use ($expense) {
            $groupId = (string) Str::uuid();
            $amount = (float) $expense->amount;

            // Resolve account codes
            $sourceAccount = Account::find($expense->source_account_id);
            $destinationAccount = Account::find($expense->account_id);

            if (!$sourceAccount || !$destinationAccount) {
                throw new \Exception('Missing account mappings (Source or Searchable account).');
            }

            $sourceCode = $sourceAccount->code;
            $destinationCode = $destinationAccount->code;

            // User said: Linked Account (Source) -> Debit, Searchable Account (Destination) -> Credit
            $debitCode = $sourceCode;
            $creditCode = $destinationCode;

            $commonPayload = [
                'id' => $expense->id,
                'expense_type' => $expense->type,
                'reference' => 'EXPENSE-' . $expense->id
            ];

            // 1. Write the two primary journal entry lines
            $this->writeEntry($groupId, $debitCode, null, $amount, 0, $expense, $commonPayload);
            $this->writeEntry($groupId, null, $creditCode, 0, $amount, $expense, $commonPayload);

            // 2. Propagate to parent accounts
            $this->propagateToParents($groupId, $debitCode, $creditCode, $amount, $expense, $commonPayload);

            // 3. Mark as approved
            $expense->update(['status' => 'approved']);
        });

        return $expense->fresh();
    }

    private function writeEntry(
        string $groupId,
        ?string $debitCode,
        ?string $creditCode,
        float $debit,
        float $credit,
        Expense $expense,
        array $payload
    ): JournalEntry {
        return JournalEntry::create([
            'transaction_group_id' => $groupId,
            'debit_account_code' => $debitCode,
            'credit_account_code' => $creditCode,
            'debit' => $debit > 0 ? $debit : null,
            'credit' => $credit > 0 ? $credit : null,
            'reference_type' => 'expense',
            'reference_id' => $expense->id,
            'description' => $expense->name_ar ?: $expense->name_en ?: 'Expense approved',
            'payload' => $payload,
        ]);
    }

    private function propagateToParents(
        string $groupId,
        string $debitCode,
        string $creditCode,
        float $amount,
        Expense $expense,
        array $payload
    ): void {
        $parentPayload = array_merge($payload, ['is_parent_propagation' => true]);

        // Propagate debit side up (Linked Account)
        $this->walkParents($debitCode, function (string $parentCode) use ($groupId, $amount, $expense, $parentPayload) {
            $this->writeEntry($groupId, $parentCode, null, $amount, 0, $expense, $parentPayload);
        });

        // Propagate credit side up (Searchable Account)
        $this->walkParents($creditCode, function (string $parentCode) use ($groupId, $amount, $expense, $parentPayload) {
            $this->writeEntry($groupId, null, $parentCode, 0, $amount, $expense, $parentPayload);
        });
    }

    private function walkParents(string $code, callable $callback): void
    {
        $parentCode = JournalEntry::getParentCode($code);
        while ($parentCode !== null) {
            $callback($parentCode);
            $parentCode = JournalEntry::getParentCode($parentCode);
        }
    }
}
