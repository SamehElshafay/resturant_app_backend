<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AccountingReportController
 *
 * Provides two core accounting reports adapted for the POS system:
 *
 * 1. getTrialBalance  — Mizan (ميزان المراجعة)
 *    Aggregates all journal_entries movements per account code, up to a given date.
 *    Supports filtering by entity type: account / user / supplier / branch.
 *
 * 2. getAccountBalance — Account Statement (كشف حساب)
 *    Shows the full movement history + running totals for a single account code.
 *    Optionally filtered by reference (e.g. a specific voucher ID).
 *
 * Design Notes (vs the reference implementation):
 * ─────────────────────────────────────────────────
 * • account_code lives in `accounts.code`, NOT in entity tables directly.
 *   Users/Suppliers are linked via `account_id` → join to `accounts.code`.
 * • `accounts` has `name_ar` and `name_en`; we show the one matching app locale.
 * • journal_entries stores both `debit` and `credit` as separate nullable columns
 *   (not a combined amount field), so aggregation is straightforward.
 * • Pagination for getTrialBalance is done in PHP (collection-level) because the
 *   UNION-ALL + GROUP BY pattern is already at DB level; pushing pagination in SQL
 *   would require a wrapping subquery which is less readable.
 * • getAccountBalance uses true Laravel paginate() for efficient DB-level paging.
 */
class AccountingReportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // 1. Trial Balance (ميزان المراجعة)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /accounting/reports/trial-balance
     *
     * Query params:
     *   date_to      – date string (default: today)
     *   report_type  – account | user | supplier | branch  (default: account)
     *   name         – free-text search against the entity name
     *   per_page     – items per page (default: 20)
     *   page         – current page (default: 1)
     */
    public function getTrialBalance(Request $request)
    {
        $dateTo = $request->date_to ?? now()->toDateString();
        $type = $request->input('report_type', 'account');
        $search = $request->input('name');

        // ── Step 1: Resolve the account codes for the requested entity type ──
        $accountCodes = $this->resolveAccountCodes($type, $search);

        if (empty($accountCodes)) {
            return $this->emptyTrialBalanceResponse($type, $search, $dateTo);
        }

        // ── Step 2: Aggregate journal entries (UNION-ALL trick for debit + credit sides) ──
        //
        // Each journal_entry row has EITHER a debit_account_code OR a credit_account_code
        // (or both for a summary row). We need to aggregate debit amounts grouped by
        // debit_account_code, then aggregate credit amounts grouped by credit_account_code,
        // then merge them. Using UNION ALL + outer GROUP BY is the most efficient approach.

        $debitQuery = DB::table('journal_entries')
            ->select(
                'debit_account_code as account_code',
                DB::raw('SUM(COALESCE(debit, 0)) as total_debit'),
                DB::raw('SUM(COALESCE(credit, 0)) as total_credit')
            )
            ->whereNotNull('debit_account_code')
            ->whereIn('debit_account_code', $accountCodes)
            ->whereDate('created_at', '<=', $dateTo)
            ->groupBy('debit_account_code');

        $creditQuery = DB::table('journal_entries')
            ->select(
                'credit_account_code as account_code',
                DB::raw('SUM(COALESCE(debit, 0)) as total_debit'),
                DB::raw('SUM(COALESCE(credit, 0)) as total_credit')
            )
            ->whereNotNull('credit_account_code')
            ->whereIn('credit_account_code', $accountCodes)
            ->whereDate('created_at', '<=', $dateTo)
            ->groupBy('credit_account_code');

        $unified = $debitQuery->unionAll($creditQuery);

        $summarized = DB::table(DB::raw("({$unified->toSql()}) as combined"))
            ->mergeBindings($unified)
            ->select(
                'account_code',
                DB::raw('SUM(total_debit) as debit'),
                DB::raw('SUM(total_credit) as credit')
            )
            ->groupBy('account_code')
            ->get();

        // ── Step 3: Map account codes → names + compute balance ──────────────
        $locale = app()->getLocale();

        // Pre-load all account names in ONE query (no N+1)
        $accountNames = DB::table('accounts')
            ->whereIn('code', $summarized->pluck('account_code')->toArray())
            ->get(['code', 'name', 'name_ar', 'name_en'])
            ->keyBy('code');

        // Pre-load entity names in ONE query per type
        $entityNames = $this->resolveEntityNames($type, $search);

        $allResults = [];
        foreach ($summarized as $row) {
            // Resolve display name
            $name = $this->resolveDisplayName($row->account_code, $type, $accountNames, $entityNames, $locale);
            if (!$name)
                continue;

            $debit = round((float) $row->debit, 2);
            $credit = round((float) $row->credit, 2);
            $balance = round($debit - $credit, 2);

            $allResults[] = (object) [
                'account_code' => $row->account_code,
                'name' => $name,
                'entity_type' => ucfirst($type),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'abs_balance' => abs($balance),
                'balance_side' => $balance >= 0 ? 'DEBIT' : 'CREDIT',
            ];
        }

        // ── Step 4: Paginate in PHP (data already aggregated at DB level) ─────
        $perPage = (int) $request->input('per_page', 20);
        $currentPage = (int) $request->input('page', 1);
        $collection = collect($allResults);
        $paginated = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'report_type' => $type,
            'name_filter' => $search,
            'date_to' => $dateTo,
            'data' => $paginated,
            'summary' => [
                'grand_total_debit' => round($collection->sum('debit'), 2),
                'grand_total_credit' => round($collection->sum('credit'), 2),
                'grand_total_net' => round($collection->sum('balance'), 2),
            ],
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => (int) ceil($collection->count() / $perPage),
                'per_page' => $perPage,
                'total' => $collection->count(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Account Balance / Statement (كشف حساب)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /accounting/reports/account-balance
     *
     * Query params:
     *   account_code   – required; the account code to inspect
     *   reference_id   – optional; filter by a specific voucher/order ID
     *   reference_type – optional; 'voucher' | 'order' (used with reference_id)
     *   per_page       – default 20
     */
    public function getAccountBalance(Request $request)
    {
        $request->validate([
            'account_code' => 'required|string',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|in:voucher,order',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $accountCode = $request->input('account_code');
        $referenceId = $request->input('reference_id');
        $referenceType = $request->input('reference_type');
        $perPage = (int) $request->input('per_page', 20);
        $locale = app()->getLocale();

        // ── Step 1: Totals (single query) ─────────────────────────────────────
        $totals = DB::table('journal_entries')
            ->where(function ($q) use ($accountCode) {
                $q->where('debit_account_code', $accountCode)
                    ->orWhere('credit_account_code', $accountCode);
            })
            ->when($referenceId, fn($q) => $q->where('reference_id', $referenceId))
            ->when($referenceType, fn($q) => $q->where('reference_type', $referenceType))
            ->selectRaw("
                SUM(CASE WHEN debit_account_code  = ? THEN COALESCE(debit,  0) ELSE 0 END) as total_debit,
                SUM(CASE WHEN credit_account_code = ? THEN COALESCE(credit, 0) ELSE 0 END) as total_credit
            ", [$accountCode, $accountCode])
            ->first();

        $totalDebit = round((float) ($totals->total_debit ?? 0), 2);
        $totalCredit = round((float) ($totals->total_credit ?? 0), 2);
        $netBalance = round($totalDebit - $totalCredit, 2);

        // ── Step 2: Paginated history ──────────────────────────────────────────
        $history = DB::table('journal_entries')
            ->select([
                'id',
                'transaction_group_id',
                'debit_account_code',
                'credit_account_code',
                DB::raw("CASE WHEN debit_account_code  = '{$accountCode}' THEN COALESCE(debit,  0) ELSE 0 END as debit"),
                DB::raw("CASE WHEN credit_account_code = '{$accountCode}' THEN COALESCE(credit, 0) ELSE 0 END as credit"),
                'description',
                'reference_type',
                'reference_id',
                'payload',
                'created_at',
            ])
            ->where(function ($q) use ($accountCode) {
                $q->where('debit_account_code', $accountCode)
                    ->orWhere('credit_account_code', $accountCode);
            })
            ->when($referenceId, fn($q) => $q->where('reference_id', $referenceId))
            ->when($referenceType, fn($q) => $q->where('reference_type', $referenceType))
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // ── Step 3: Enrich descriptions (batch account name lookup) ────────────
        $allCodes = collect($history->items())
            ->flatMap(fn($e) => [$e->debit_account_code, $e->credit_account_code])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Batch fetch names — ONE query, no N+1
        $nameMap = DB::table('accounts')
            ->whereIn('code', $allCodes)
            ->get(['code', 'name_ar', 'name_en', 'name'])
            ->keyBy('code')
            ->map(
                fn($a) => $locale === 'ar'
                ? ($a->name_ar ?: $a->name_en ?: $a->name)
                : ($a->name_en ?: $a->name_ar ?: $a->name)
            );

        $items = collect($history->items())->map(function ($entry) use ($nameMap) {
            $entry->debit_account_name = $nameMap[$entry->debit_account_code] ?? ($entry->debit_account_code ?? '—');
            $entry->credit_account_name = $nameMap[$entry->credit_account_code] ?? ($entry->credit_account_code ?? '—');
            return $entry;
        });

        // ── Step 4: Account meta ───────────────────────────────────────────────
        $account = DB::table('accounts')
            ->where('code', $accountCode)
            ->first(['name_ar', 'name_en', 'name', 'type', 'parent_id']);

        $accountName = $account
            ? ($locale === 'ar'
                ? ($account->name_ar ?: $account->name_en ?: $account->name)
                : ($account->name_en ?: $account->name_ar ?: $account->name))
            : $accountCode;

        // Resolve which entity type owns this account code
        $entityType = $this->inferEntityType($accountCode);

        return response()->json([
            'success' => true,
            'data' => $items,
            'summary' => [
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_type' => $this->accountTypeName($account?->type),
                'entity_type' => $entityType,
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'total' => abs($netBalance),
                'status' => [
                    'id' => $netBalance == 0 ? 0 : ($netBalance > 0 ? 1 : 2),
                    'name' => $netBalance == 0
                        ? ($locale === 'ar' ? 'صفر / مسدد' : 'Zero / Settled')
                        : ($netBalance > 0
                            ? ($locale === 'ar' ? 'مدين (مستحق عليه)' : 'Debit Balance (Owes)')
                            : ($locale === 'ar' ? 'دائن (مستحق له)' : 'Credit Balance (Owed to)')),
                ],
            ],
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get the list of account codes for a given entity type.
     * All lookups go through the `accounts` table via `account_id` FK.
     */
    private function resolveAccountCodes(string $type, ?string $search): array
    {
        return match ($type) {
            // Direct accounts table lookup
            'account' => DB::table('accounts')
                ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                        $q->where('name_ar', 'LIKE', "%{$search}%")
                        ->orWhere('name_en', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                    }))
                ->whereNotNull('code')
                ->pluck('code')
                ->filter()
                ->toArray(),

            // Users linked to an account
            'user' => DB::table('users')
                ->join('accounts', 'users.account_id', '=', 'accounts.id')
                ->when($search, fn($q) => $q->where('users.name', 'LIKE', "%{$search}%"))
                ->whereNotNull('accounts.code')
                ->pluck('accounts.code')
                ->filter()
                ->toArray(),

            // Suppliers linked to an account
            'supplier' => DB::table('suppliers')
                ->join('accounts', 'suppliers.account_id', '=', 'accounts.id')
                ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                        $q->where('suppliers.name_ar', 'LIKE', "%{$search}%")
                        ->orWhere('suppliers.name_en', 'LIKE', "%{$search}%");
                    }))
                ->whereNotNull('accounts.code')
                ->pluck('accounts.code')
                ->filter()
                ->toArray(),

            // Branches (via parent_account_id stored in branches.parent_account_id)
            'branch' => DB::table('branches')
                ->join('accounts', 'branches.parent_account_id', '=', 'accounts.id')
                ->when($search, fn($q) => $q->where('branches.name', 'LIKE', "%{$search}%"))
                ->whereNotNull('accounts.code')
                ->pluck('accounts.code')
                ->filter()
                ->toArray(),

            default => [],
        };
    }

    /**
     * Pre-load entity names for display (ONE query per type, not per row).
     * Returns a map of account_code → display_name.
     */
    private function resolveEntityNames(string $type, ?string $search): array
    {
        $locale = app()->getLocale();

        return match ($type) {
            'account' => [], // handled via accountNames map

            'user' => DB::table('users')
                ->join('accounts', 'users.account_id', '=', 'accounts.id')
                ->when($search, fn($q) => $q->where('users.name', 'LIKE', "%{$search}%"))
                ->select('accounts.code', 'users.name')
                ->get()
                ->pluck('name', 'code')
                ->toArray(),

            'supplier' => DB::table('suppliers')
                ->join('accounts', 'suppliers.account_id', '=', 'accounts.id')
                ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                        $q->where('suppliers.name_ar', 'LIKE', "%{$search}%")
                        ->orWhere('suppliers.name_en', 'LIKE', "%{$search}%");
                    }))
                ->select(
                    'accounts.code',
                    DB::raw(
                        $locale === 'ar'
                        ? "COALESCE(suppliers.name_ar, suppliers.name_en, suppliers.name) as name"
                        : "COALESCE(suppliers.name_en, suppliers.name_ar, suppliers.name) as name"
                    )
                )
                ->get()
                ->pluck('name', 'code')
                ->toArray(),

            'branch' => DB::table('branches')
                ->join('accounts', 'branches.parent_account_id', '=', 'accounts.id')
                ->when($search, fn($q) => $q->where('branches.name', 'LIKE', "%{$search}%"))
                ->select('accounts.code', 'branches.name')
                ->get()
                ->pluck('name', 'code')
                ->toArray(),

            default => [],
        };
    }

    /**
     * Resolve a human-readable display name for a given account code.
     */
    private function resolveDisplayName(
        string $code,
        string $type,
        $accountNames,
        array $entityNames,
        string $locale
    ): ?string {
        if ($type === 'account') {
            $a = $accountNames[$code] ?? null;
            if (!$a)
                return null;
            return $locale === 'ar'
                ? ($a->name_ar ?: $a->name_en ?: $a->name)
                : ($a->name_en ?: $a->name_ar ?: $a->name);
        }

        return $entityNames[$code] ?? null;
    }

    /**
     * Infer the owner entity type of an account code by checking foreign key links.
     * Used in the account statement summary.
     */
    private function inferEntityType(string $code): string
    {
        // Check users
        if (
            DB::table('users')->join('accounts', 'users.account_id', '=', 'accounts.id')
                ->where('accounts.code', $code)->exists()
        ) {
            return 'user';
        }
        // Check suppliers
        if (
            DB::table('suppliers')->join('accounts', 'suppliers.account_id', '=', 'accounts.id')
                ->where('accounts.code', $code)->exists()
        ) {
            return 'supplier';
        }
        // Check branches
        if (
            DB::table('branches')->join('accounts', 'branches.parent_account_id', '=', 'accounts.id')
                ->where('accounts.code', $code)->exists()
        ) {
            return 'branch';
        }

        return 'account';
    }

    /**
     * Human-readable account type from numeric code.
     * 1=Asset, 2=Liability, 3=Equity, 4=Income, 5=Expense
     */
    private function accountTypeName(?int $type): string
    {
        return match ($type) {
            1 => app()->getLocale() === 'ar' ? 'أصول' : 'Asset',
            2 => app()->getLocale() === 'ar' ? 'التزامات' : 'Liability',
            3 => app()->getLocale() === 'ar' ? 'حقوق الملكية' : 'Equity',
            4 => app()->getLocale() === 'ar' ? 'إيرادات' : 'Income',
            5 => app()->getLocale() === 'ar' ? 'مصروفات' : 'Expense',
            default => app()->getLocale() === 'ar' ? 'غير معروف' : 'Unknown',
        };
    }

    /**
     * Returns an empty trial balance response structure.
     */
    private function emptyTrialBalanceResponse(string $type, ?string $search, string $dateTo): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'report_type' => $type,
            'name_filter' => $search,
            'date_to' => $dateTo,
            'data' => [],
            'summary' => ['grand_total_debit' => 0, 'grand_total_credit' => 0, 'grand_total_net' => 0],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
        ]);
    }
}
