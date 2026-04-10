<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingEntityConfig;
use App\Models\JournalEntry;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * VoucherService
 *
 * Responsible for:
 * 1. Posting a voucher (status DRAFT → POSTED) by generating journal entry lines.
 * 2. Propagating the effect to all parent accounts recursively.
 *
 * Voucher Rules:
 *  ┌────────────┬─────────────────────────────────────────────────────────────────────┐
 *  │ RECEIPT    │ Money IN   → Debit  cash/bank account | Credit the paying party     │
 *  │ PAYMENT    │ Money OUT  → Debit  the receiving party | Credit cash/bank account  │
 *  │ TRANSFER   │ Bank move  → Debit  recipient account  | Credit source account      │
 *  └────────────┴─────────────────────────────────────────────────────────────────────┘
 *
 * Each voucher generates exactly 2 journal-entry rows (1 debit + 1 credit) per posting,
 * plus additional rows for any parent accounts affected.
 */
class VoucherService
{
    /**
     * Post a voucher: validate, build journal entries, propagate to parents.
     *
     * @throws \Exception
     */
    public function post(Voucher $voucher, int $postedBy): Voucher
    {
        if (!$voucher->isDraft()) {
            throw new \Exception("Voucher #{$voucher->id} is already {$voucher->status} and cannot be posted again.");
        }

        DB::transaction(function () use ($voucher, $postedBy) {
            $groupId = (string) Str::uuid();
            $amount = (float) $voucher->amount;

            // ── Determine debit / credit account codes based on voucher type ──────
            [$debitCode, $creditCode] = $this->resolveAccounts($voucher);

            // ── Write the two primary journal entry lines ─────────────────────────
            $commonPayload = [
                'voucher_id' => $voucher->id,
                'voucher_type' => $voucher->voucher_type,
            ];

            // Line 1: Debit side (amount on debit, credit = 0)
            $this->writeEntry($groupId, $debitCode, null, $amount, 0, $voucher, $commonPayload);

            // Line 2: Credit side (credit = amount, debit = 0)
            $this->writeEntry($groupId, null, $creditCode, 0, $amount, $voucher, $commonPayload);

            // ── Propagate to parent accounts ──────────────────────────────────────
            $this->propagateToParents($groupId, $debitCode, $creditCode, $amount, $voucher, $commonPayload);

            // ── Mark voucher as posted ────────────────────────────────────────────
            $voucher->update([
                'status' => 'POSTED',
                'posted_by' => $postedBy,
                'posted_at' => Carbon::now(),
            ]);
        });

        return $voucher->fresh();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Resolve Debit & Credit Account Codes
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns [debit_code, credit_code] depending on voucher type.
     *
     * RECEIPT  → Debit: cash/bank account | Credit: main party account (account_code)
     * PAYMENT  → Debit: main party account (account_code) | Credit: cash/bank account
     * TRANSFER → Debit: recipient account | Credit: source account (account_code)
     */
    private function resolveAccounts(Voucher $voucher): array
    {
        $partyAccount = $voucher->account_code;    // The entity (employee/supplier/customer/etc.)
        $cashOrBank = $this->resolveCashOrBank($voucher);

        return match ($voucher->voucher_type) {
            'RECEIPT' => [$cashOrBank, $partyAccount],         // Cash ↑, Party ↑ credit
            'PAYMENT' => [$partyAccount, $cashOrBank],         // Party ↓ debit, Cash ↓
            'TRANSFER' => [$voucher->recipient_account_code, $partyAccount], // Recipient ↑, Source ↓
            default => throw new \Exception("Unknown voucher type: {$voucher->voucher_type}"),
        };
    }

    /**
     * Determine the cash or bank account to use:
     * - If bank_amount > 0 and bank_account_code is set → use bank
     * - Otherwise → use treasury_account_code
     */
    private function resolveCashOrBank(Voucher $voucher): string
    {
        if ($voucher->bank_amount > 0 && $voucher->bank_account_code) {
            return $voucher->bank_account_code;
        }
        if ($voucher->treasury_account_code) {
            return $voucher->treasury_account_code;
        }
        throw new \Exception("Voucher #{$voucher->id} has no treasury_account_code or bank_account_code defined.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Write a Journal Entry Row
    // ──────────────────────────────────────────────────────────────────────────

    private function writeEntry(
        string $groupId,
        ?string $debitCode,
        ?string $creditCode,
        float $debit,
        float $credit,
        Voucher $voucher,
        array $payload
    ): JournalEntry {
        return JournalEntry::create([
            'transaction_group_id' => $groupId,
            'debit_account_code' => $debitCode,
            'credit_account_code' => $creditCode,
            'debit' => $debit > 0 ? $debit : null,
            'credit' => $credit > 0 ? $credit : null,
            'reference_type' => 'voucher',
            'reference_id' => $voucher->id,
            'description' => $voucher->note,
            'payload' => $payload,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Parent Account Propagation
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Walk up the account tree for both the debit & credit accounts,
     * writing a journal entry line at each parent level.
     *
     * e.g. account code "101-003-007"
     *   → parents: "101-003", then "101"
     */
    private function propagateToParents(
        string $groupId,
        string $debitCode,
        string $creditCode,
        float $amount,
        Voucher $voucher,
        array $payload
    ): void {
        $parentPayload = array_merge($payload, ['is_parent_propagation' => true]);

        // Propagate debit side up
        $this->walkParents($debitCode, function (string $parentCode) use ($groupId, $amount, $voucher, $parentPayload) {
            $this->writeEntry($groupId, $parentCode, null, $amount, 0, $voucher, $parentPayload);
        });

        // Propagate credit side up
        $this->walkParents($creditCode, function (string $parentCode) use ($groupId, $amount, $voucher, $parentPayload) {
            $this->writeEntry($groupId, null, $parentCode, 0, $amount, $voucher, $parentPayload);
        });
    }

    /**
     * Recursively extract the parent code from an account code and call $callback.
     * "101-003-007" → calls callback("101-003"), then callback("101"), then stops.
     */
    private function walkParents(string $code, callable $callback): void
    {
        $parentCode = JournalEntry::getParentCode($code);
        while ($parentCode !== null) {
            $callback($parentCode);
            $parentCode = JournalEntry::getParentCode($parentCode);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Account Code Generation for new Users
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generates an account code (XXX-YYY) for a new user based on their entity type.
     * Looks up accounting_entity_configs to get the parent code (XXX),
     * then finds the next available sequential child number (YYY).
     *
     * @param string $entityType  e.g. 'cashier', 'admin', 'manager'
     * @return string|null  e.g. '201-001', or null if no config found
     */
    public static function generateAccountCode(string $entityType): ?string
    {
        $config = AccountingEntityConfig::where('entity_type', $entityType)->first();
        if (!$config) {
            return null; // No config → no auto-account
        }

        $parentCode = $config->parent_account_code;

        // Find the next sequential child number under this parent
        $existing = Account::where('code', 'like', "{$parentCode}-%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) DESC")
            ->first();

        $nextSeq = 1;
        if ($existing) {
            $parts = explode('-', $existing->code);
            $lastSeg = (int) end($parts);
            $nextSeq = $lastSeg + 1;
        }

        return $parentCode . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
