<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountingScenario;
use App\Models\AccountingScenarioStep;

class AccountingScenarioSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Purchase Invoice Approval
        $purchase = AccountingScenario::create([
            'name' => 'Supplier Invoice Approval',
            'event_key' => 'PURCHASE_INVOICE_APPROVE',
            'trigger_type' => 'MANUAL',
            'is_active' => true,
        ]);

        AccountingScenarioStep::create([
            'scenario_id' => $purchase->id,
            'description' => 'Inventory increase & Supplier Credit',
            'debit_account_pattern' => '004', // Store Raw
            'credit_account_pattern' => '{{supplier_account}}',
            'amount_formula' => '{{total_amount}}',
            'priority' => 10,
        ]);

        // 2. Product Production
        $production = AccountingScenario::create([
            'name' => 'Product Production (Assembly)',
            'event_key' => 'PRODUCTION_COMPLETE',
            'trigger_type' => 'MANUAL',
            'is_active' => true,
        ]);

        AccountingScenarioStep::create([
            'scenario_id' => $production->id,
            'description' => 'Transfer from Raw to Finished Goods',
            'debit_account_pattern' => '005', // Store Products
            'credit_account_pattern' => '004', // Store Raw (Assuming ingredients cost is the basis)
            'amount_formula' => '{{total_cost}}',
            'priority' => 10,
        ]);

        // 3. Branch Transfer
        $transfer = AccountingScenario::create([
            'name' => 'Stock Transfer to Branch',
            'event_key' => 'BRANCH_TRANSFER',
            'trigger_type' => 'MANUAL',
            'is_active' => true,
        ]);

        AccountingScenarioStep::create([
            'scenario_id' => $transfer->id,
            'description' => 'Move finished goods to branch inventory',
            'debit_account_pattern' => '{{branch_account}}',
            'credit_account_pattern' => '005', // Store Products
            'amount_formula' => '{{total_value}}',
            'priority' => 10,
        ]);
    }
}
