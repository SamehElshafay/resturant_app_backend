<?php

namespace App\Http\Controllers;

use App\Models\AccountingScenario;
use App\Models\AccountingScenarioStep;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountingScenarioController extends Controller
{
    public function index()
    {
        $scenarios = AccountingScenario::withCount('steps')->get();
        return view('accounting.scenarios.index', compact('scenarios'));
    }

    public function show(AccountingScenario $scenario)
    {
        $scenario->load('steps');
        $accounts = Account::orderBy('code')->get();
        return view('accounting.scenarios.show', compact('scenario', 'accounts'));
    }

    public function storeStep(Request $request, AccountingScenario $scenario)
    {
        $request->validate([
            'description' => 'required|string',
            'debit_account_pattern' => 'required|string',
            'credit_account_pattern' => 'required|string',
            'debit_amount_formula' => 'nullable|string',
            'credit_amount_formula' => 'nullable|string',
            'condition_expression' => 'nullable|string',
            'priority' => 'required|integer',
        ]);

        $scenario->steps()->create($request->all());

        return back()->with('success', 'Step added successfully.');
    }

    public function updateStep(Request $request, AccountingScenarioStep $step)
    {
        $request->validate([
            'description' => 'required|string',
            'debit_account_pattern' => 'required|string',
            'credit_account_pattern' => 'required|string',
            'debit_amount_formula' => 'nullable|string',
            'credit_amount_formula' => 'nullable|string',
            'condition_expression' => 'nullable|string',
            'priority' => 'required|integer',
        ]);

        $step->update($request->all());

        return back()->with('success', 'Step updated successfully.');
    }

    public function destroyStep(AccountingScenarioStep $step)
    {
        $step->delete();
        return back()->with('success', 'Step deleted successfully.');
    }

    public function toggleScenario(AccountingScenario $scenario)
    {
        $scenario->update(['is_active' => !$scenario->is_active]);
        return back()->with('success', 'Scenario status updated.');
    }
}
