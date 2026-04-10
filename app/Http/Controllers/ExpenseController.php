<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Branch;
use App\Models\Account;
use App\Models\AccountingEntityConfig;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }
    public function index(Request $request)
    {
        $query = Expense::with(['branch', 'account'])->latest();

        // Date Filtering
        $filter = $request->get('date_filter', 'today');
        $statusFilter = $request->get('status_filter', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($statusFilter != 'all') {
            $query->where('status', $statusFilter);
        }

        if ($filter == 'today') {
            $query->whereDate('expense_date', now()->today());
        } elseif ($filter == 'yesterday') {
            $query->whereDate('expense_date', now()->yesterday());
        } elseif ($filter == 'this_week') {
            $query->whereBetween('expense_date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter == 'this_month') {
            $query->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($filter == 'custom' && $fromDate && $toDate) {
            $query->whereBetween('expense_date', [$fromDate, $toDate]);
        }

        $expenses = $query->get();
        $branches = Branch::all();
        $accounts = Account::all();
        
        // Fetch Mapping for Expenses and Petty Cash from Entity Configs
        $expenseConfig = AccountingEntityConfig::where('entity_type', 'GENERAL_EXPENSE')->first();
        $pettyCashConfig = AccountingEntityConfig::where('entity_type', 'PETTY__CASH')->first();

        $expenseAccount = $expenseConfig ? Account::where('code', $expenseConfig->parent_account_code)->first() : null;
        $pettyCashAccount = $pettyCashConfig ? Account::where('code', $pettyCashConfig->parent_account_code)->first() : null;

        $accountMappings = [
            'expense' => $expenseAccount ? ['id' => $expenseAccount->id, 'name' => $expenseAccount->name_ar ?? $expenseAccount->name_en, 'code' => $expenseAccount->code] : null,
            'petty_cash' => $pettyCashAccount ? ['id' => $pettyCashAccount->id, 'name' => $pettyCashAccount->name_ar ?? $pettyCashAccount->name_en, 'code' => $pettyCashAccount->code] : null,
        ];

        // Totals should probably still be for the current month for the cards, or match the filter? 
        // Let's keep cards as monthly totals for context, but can change if needed.
        $totalThisMonth = Expense::whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->where('status', '!=', 'cancelled')->sum('amount');
        $pettyCashThisMonth = Expense::where('type', 'petty_cash')->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->where('status', '!=', 'cancelled')->sum('amount');
        $expenseCount = $expenses->count();

        return view('expenses.index', compact('expenses', 'totalThisMonth', 'pettyCashThisMonth', 'expenseCount', 'branches', 'accounts', 'filter', 'fromDate', 'toDate', 'statusFilter', 'accountMappings'));
    }

    public function create()
    {
        $branches = Branch::all();
        $accounts = Account::all(); // Should filter by type Expenses
        return view('expenses.create', compact('branches', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'account_id' => 'nullable|exists:accounts,id',
            'source_account_id' => 'nullable|exists:accounts,id',
            'type' => 'required|in:expense,petty_cash',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            Expense::create([
                'branch_id' => $request->branch_id,
                'account_id' => $request->account_id,
                'source_account_id' => $request->source_account_id,
                'type' => $request->type,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'name_ar' => $request->name_ar,
                'name_en' => $request->name_en,
                'description' => $request->description,
                'created_by' => Auth::id() ?? 1,
            ]);

            // Here we should also create a Journal Entry
            // Debit Expense Account
            // Credit Cash/Bank Account (Petty Cash usually from Cash on Hand)

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording expense: ' . $e->getMessage())->withInput();
        }
    }

    public function approve(Expense $expense)
    {
        try {
            $this->expenseService->approve($expense);
            return back()->with('success', 'Expense approved and journal entries generated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelStatus(Expense $expense)
    {
        if ($expense->status === 'approved') {
            return back()->with('error', 'Approved expenses cannot be cancelled.');
        }

        $expense->update(['status' => 'cancelled']);
        return back()->with('success', 'Expense has been cancelled.');
    }
    public function update(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Only pending expenses can be edited.');
        }

        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'account_id' => 'nullable|exists:accounts,id',
            'source_account_id' => 'nullable|exists:accounts,id',
            'type' => 'required|in:expense,petty_cash',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
        ]);

        $expense->update($request->all());

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }
    public function show(Expense $expense)
    {
        return redirect()->route('expenses.index', ['open_id' => $expense->id]);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Expense record deleted.');
    }
}
