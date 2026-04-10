<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    /**
     * Display chart of accounts tree with balances.
     */
    public function chart(Request $request)
    {
        // 1. Fetch all accounts with basic filtering
        $query = Account::query();
        
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'LIKE', "%$s%")
                  ->orWhere('name_ar', 'LIKE', "%$s%")
                  ->orWhere('name_en', 'LIKE', "%$s%")
                  ->orWhere('code', 'LIKE', "%$s%");
            });
        }

        $allAccounts = $query->orderBy('code')->get();

        // 2. Fetch current balances from journal_entries
        $debitBalances = \Illuminate\Support\Facades\DB::table('journal_entries')
            ->select('debit_account_code', \Illuminate\Support\Facades\DB::raw('SUM(debit) as total_debit'))
            ->whereNotNull('debit_account_code')
            ->groupBy('debit_account_code')
            ->pluck('total_debit', 'debit_account_code');

        $creditBalances = \Illuminate\Support\Facades\DB::table('journal_entries')
            ->select('credit_account_code', \Illuminate\Support\Facades\DB::raw('SUM(credit) as total_credit'))
            ->whereNotNull('credit_account_code')
            ->groupBy('credit_account_code')
            ->pluck('total_credit', 'credit_account_code');

        // 3. Map balances back to accounts
        foreach ($allAccounts as $account) {
            $dr = (float) ($debitBalances[$account->code] ?? 0);
            $cr = (float) ($creditBalances[$account->code] ?? 0);
            $account->balance = $dr - $cr;
        }

        // 4. Build hierarchy (recursive)
        $accountsById = $allAccounts->keyBy('id');
        $rootAccounts = collect();

        foreach ($allAccounts as $account) {
            if ($account->parent_id && isset($accountsById[$account->parent_id])) {
                $parent = $accountsById[$account->parent_id];
                if (!$parent->relationLoaded('children')) {
                    $parent->setRelation('children', collect());
                }
                $parent->getRelation('children')->push($account);
            } else {
                // Only consider it root if it has no parent OR its parent was filtered out
                $rootAccounts->push($account);
            }
        }

        // 5. Hierarchy Balance Assignment
        // Since AccountingEngine already propagates transactions to parent accounts in the DB,
        // we don't need to recursively sum children at runtime; the parent's account->balance 
        // already contains the rolled-up total.
        foreach ($allAccounts as $account) {
            $account->total_hierarchy_balance = $account->balance;
        }

        // 6. Post-aggregation filtering (e.g. by balance status)
        if ($request->balance_status) {
            $rootAccounts = $rootAccounts->filter(function($account) use ($request) {
                if ($request->balance_status == 'nonzero') return abs($account->total_hierarchy_balance) > 0.01;
                if ($request->balance_status == 'debit') return $account->total_hierarchy_balance > 0.01;
                if ($request->balance_status == 'credit') return $account->total_hierarchy_balance < -0.01;
                return true;
            });
        }

        $branches = \App\Models\Branch::all();

        return view('accounting.chart', [
            'accounts' => $rootAccounts,
            'branches' => $branches,
            'filters' => $request->only(['type', 'branch_id', 'search', 'balance_status'])
        ]);
    }

    public function getNextCode(Request $request)
    {
        $parentId = $request->parent_id;
        
        if ($parentId) {
            $parent = Account::find($parentId);
            if (!$parent) return response()->json(['code' => '']);
            
            $siblingCount = Account::where('parent_id', $parentId)->count();
            $childNumber = str_pad($siblingCount + 1, 3, '0', STR_PAD_LEFT);
            return response()->json(['code' => $parent->code . '-' . $childNumber]);
        } else {
            $rootCount = Account::whereNull('parent_id')->count();
            return response()->json(['code' => str_pad($rootCount + 1, 3, '0', STR_PAD_LEFT)]);
        }
    }

    /**
     * Store a new account.
     */
    public function storeAccount(Request $request)
    {
        $request->validate([
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
            'type' => 'required|in:1,2,3,4,5',
            'parent_id' => 'nullable|exists:accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Ensure at least one name is provided
        if (empty($request->name_ar) && empty($request->name_en)) {
            return back()->withErrors(['name' => 'At least one name (Arabic or English) is required.']);
        }

        $account = new Account();
        $account->fill($request->all());
        $account->name = $request->name_en ?: $request->name_ar;
        $account->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.account_added_success')]);
        }

        return redirect()->back()->with('success', __('messages.account_added_success'));
    }

    /**
     * Update an account.
     */
    public function updateAccount(Request $request, Account $account)
    {
        $request->validate([
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
            'code' => 'required|unique:accounts,code,' . $account->id,
            'type' => 'required|in:1,2,3,4,5',
        ]);

        // Ensure at least one name is provided
        if (empty($request->name_ar) && empty($request->name_en)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'At least one name (Arabic or English) is required.'], 422);
            }
            return back()->withErrors(['name' => 'At least one name (Arabic or English) is required.']);
        }

        $account->fill($request->all());
        $account->name = $request->name_en ?: $request->name_ar;
        $account->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.account_updated_success')]);
        }

        return redirect()->back()->with('success', __('messages.account_updated_success'));
    }

    /**
     * Delete an account.
     */
    public function destroyAccount(Account $account)
    {
        $account->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.account_deleted_success')]);
        }

        return redirect()->back()->with('success', __('messages.account_deleted_success'));
    }

    /**
     * Vouchers view.
     */
    public function vouchers()
    {
        return view('accounting.vouchers');
    }

    /**
     * Entity Accounting Configs view.
     */
    public function entityConfigs()
    {
        $configs = \App\Models\AccountingEntityConfig::all();
        $merchantBillConfig = $configs->where('entity_type', 'MERCHANT_BILL_DEBIT')->first();
        $prodRawConfig = $configs->where('entity_type', 'PRODUCTION_RAW_MATERIALS')->first();
        $prodFinishedConfig = $configs->where('entity_type', 'PRODUCTION_FINISHED_GOODS')->first();
        $prodProfitConfig = $configs->where('entity_type', 'PRODUCTION_PROFIT_ACCOUNT')->first();
        
        // Filter out specific configs from general list
        $specialTypes = ['MERCHANT_BILL_DEBIT', 'PRODUCTION_RAW_MATERIALS', 'PRODUCTION_FINISHED_GOODS', 'PRODUCTION_PROFIT_ACCOUNT'];
        $generalConfigs = $configs->whereNotIn('entity_type', $specialTypes);

        $configuredTypes = $configs->pluck('entity_type')->toArray();
        $types = \App\Models\AccountingEntityType::whereNotIn('name', $configuredTypes)->get();
        $accounts = Account::orderBy('code')->get();
        return view('accounting.entity_configs', [
            'configs' => $generalConfigs,
            'types' => $types,
            'accounts' => $accounts,
            'merchantBillConfig' => $merchantBillConfig,
            'prodRawConfig' => $prodRawConfig,
            'prodFinishedConfig' => $prodFinishedConfig,
            'prodProfitConfig' => $prodProfitConfig
        ]);
    }

    /**
     * Store entity config.
     */
    public function storeEntityConfig(Request $request)
    {
        $special = ['MERCHANT_BILL_DEBIT', 'PRODUCTION_RAW_MATERIALS', 'PRODUCTION_FINISHED_GOODS', 'PRODUCTION_PROFIT_ACCOUNT'];
        if (in_array($request->entity_type, $special)) {
            $request->validate([
                'parent_account_code' => 'required|exists:accounts,code',
            ]);
            
            \App\Models\AccountingEntityConfig::updateOrCreate(
                ['entity_type' => $request->entity_type],
                ['parent_account_code' => $request->parent_account_code]
            );
            return redirect()->back()->with('success', 'Configuration updated successfully');
        }

        $request->validate([
            'entity_type' => 'required|string|unique:accounting_entity_configs,entity_type',
            'parent_account_code' => 'required|exists:accounts,code',
        ]);

        \App\Models\AccountingEntityConfig::create($request->all());
        return redirect()->back()->with('success', 'Config created successfully');
    }

    /**
     * Store a new entity type.
     */
    public function storeEntityType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:accounting_entity_types,name',
            'display_name' => 'nullable|string',
        ]);

        \App\Models\AccountingEntityType::create($request->all());
        return redirect()->back()->with('success', 'Entity Type added successfully');
    }

    /**
     * Delete an entity type.
     */
    public function destroyEntityType(\App\Models\AccountingEntityType $type)
    {
        $type->delete();
        return redirect()->back()->with('success', 'Entity Type deleted successfully');
    }

    /**
     * Destroy entity config.
     */
    public function destroyEntityConfig(\App\Models\AccountingEntityConfig $config)
    {
        $config->delete();
        return redirect()->back()->with('success', 'Config deleted successfully');
    }

    /**
     * Accounting reports view (Trial Balance + Account Statement).
     */
    public function reports()
    {
        return view('accounting.reports');
    }

    /**
     * Account Statement view.
     */
    public function statement(Request $request, Account $account)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Use JournalEntry instead of Ledger
        $code = $account->code;

        // Get initial balance before start date
        $openingBalanceDebit = \App\Models\JournalEntry::where('debit_account_code', $code)
            ->whereDate('created_at', '<', $startDate)
            ->sum('debit') ?: 0;

        $openingBalanceCredit = \App\Models\JournalEntry::where('credit_account_code', $code)
            ->whereDate('created_at', '<', $startDate)
            ->sum('credit') ?: 0;

        $openingBalance = $openingBalanceDebit - $openingBalanceCredit;

        // Get entries in range (Debit or Credit)
        $entries = \App\Models\JournalEntry::where(function($q) use ($code) {
                $q->where('debit_account_code', $code)
                  ->orWhere('credit_account_code', $code);
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at')
            ->get();

        return view('accounting.statement', compact('account', 'entries', 'openingBalance', 'startDate', 'endDate'));
    }
}
