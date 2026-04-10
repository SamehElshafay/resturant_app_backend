<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\PosDevice;
use App\Models\Account;
use App\Services\VoucherService;

// 1. Identify "Restaurants" parent account
$restaurantsParent = Account::where('code', '002')->first();
if (!$restaurantsParent) {
    echo "Error: Restaurants parent account (002) not found.\n";
    exit;
}

// 2. Fix Branches
$branches = Branch::all();
foreach ($branches as $b) {
    echo "Processing branch: {$b->name}\n";

    // Look for existing account named similar to branch or with matching old code
    // We'll search for accounts where name starts with "Branch: " or matches branch name exactly
    $account = Account::where('name', "Branch: {$b->name}")
        ->orWhere('name', $b->name)
        ->first();

    if ($account) {
        $b->update([
            'account_id' => $account->id,
            'account_code' => $account->code
        ]);
        echo "  - Linked to existing account: {$account->code}\n";
    } else {
        // Create new account under Restaurants
        $newCode = VoucherService::generateAccountCode('002');
        $account = Account::create([
            'name' => "Branch: " . $b->name,
            'name_ar' => $b->name_ar ?? $b->name,
            'name_en' => $b->name_en ?? $b->name,
            'parent_id' => $restaurantsParent->id,
            'type' => 1, // Asset
            'branch_id' => $b->id,
            'code' => $newCode
        ]);
        $b->update([
            'account_id' => $account->id,
            'account_code' => $account->code
        ]);
        echo "  - Created new account under 002: {$account->code}\n";
    }
}

// 3. Fix POS Devices
$posDevices = PosDevice::all();
foreach ($posDevices as $pos) {
    echo "Processing POS: {$pos->name} (Branch: {$pos->branch->name})\n";

    $branch = $pos->branch;
    if (!$branch || !$branch->account_id) {
        echo "  - Error: Branch not linked to account.\n";
        continue;
    }

    $parentAccount = Account::find($branch->account_id);
    $parentCode = $parentAccount->code;

    // Check if current POS account code matches prefix
    if ($pos->account_code && strpos($pos->account_code, $parentCode . '-') === 0) {
        echo "  - Code already correct: {$pos->account_code}\n";
    } else {
        // Generate new code
        $newCode = VoucherService::generateAccountCode($parentCode);

        // If it already has an account, update its code
        if ($pos->account_id) {
            $account = Account::find($pos->account_id);
            $account->update(['code' => $newCode, 'parent_id' => $parentAccount->id]);
            $pos->update(['account_code' => $newCode]);
            echo "  - Updated existing account code to: {$newCode}\n";
        } else {
            // Create new account
            $account = Account::create([
                'name' => "POS - " . $pos->name,
                'parent_id' => $parentAccount->id,
                'type' => 1, // Asset
                'branch_id' => $pos->branch_id,
                'code' => $newCode
            ]);
            $pos->update([
                'account_id' => $account->id,
                'account_code' => $account->code
            ]);
            echo "  - Created new POS account: {$newCode}\n";
        }
    }
}

echo "Cleanup done.\n";
