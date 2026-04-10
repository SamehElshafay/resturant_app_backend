<?php
use App\Models\AccountingScenario;
use App\Models\AccountingScenarioStep;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::beginTransaction();

try {
    // 1. Production Complete Scenario
    $scenario = AccountingScenario::updateOrCreate(
        ['event_key' => 'PRODUCTION_COMPLETE'],
        [
            'name' => 'Production Process',
            'description' => 'Transfer costs and profits from raw materials to branch products',
            'is_active' => true,
        ]
    );

    // Clear existing steps
    AccountingScenarioStep::where('scenario_id', $scenario->id)->delete();

    // Step 1: Debit Branch Stock (Full Selling Price) and Credit Profit Account (Initial)
    AccountingScenarioStep::create([
        'scenario_id' => $scenario->id,
        'description' => 'Transfer Products to Branch (Selling Price)',
        'debit_account_pattern' => '{{branch_products_account}}',
        'credit_account_pattern' => '{{profit_account}}',
        'debit_amount_formula' => '{{selling_amount}}',
        'is_active' => true,
        'priority' => 1
    ]);

    // Step 2: Debit Profit Account for Cost and Credit Raw Materials
    AccountingScenarioStep::create([
        'scenario_id' => $scenario->id,
        'description' => 'Accounting for Raw Materials Cost',
        'debit_account_pattern' => '{{profit_account}}',
        'credit_account_pattern' => '{{raw_materials_account}}',
        'debit_amount_formula' => '{{purchase_cost}}',
        'is_active' => true,
        'priority' => 2
    ]);

    DB::commit();
    echo "Production Scenario Updated Successful!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
