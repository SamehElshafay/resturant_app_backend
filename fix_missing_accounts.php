<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Account;
use App\Models\PosDevice;
use App\Services\VoucherService;

$branches = Branch::whereNull('account_id')->get();
echo "Found " . $branches->count() . " branches without accounts.\n";

foreach ($branches as $branch) {
    echo "Processing branch: {$branch->name}...\n";

    // Check if an account already exists with this name or code if we can guess it
    $account = Account::create([
        'name' => $branch->name,
        'name_ar' => $branch->name_ar ?? $branch->name,
        'name_en' => $branch->name_en ?? $branch->name,
        'type' => 1, // Asset
        'branch_id' => $branch->id,
    ]);

    $branch->update([
        'account_id' => $account->id,
        'account_code' => $account->code
    ]);
    echo "Created account {$account->code} for branch {$branch->id}\n";
}

// Now fix POS devices
$posDevices = PosDevice::whereNull('account_id')->get();
echo "Found " . $posDevices->count() . " POS devices without accounts.\n";

foreach ($posDevices as $pos) {
    if (!$pos->branch || !$pos->branch->account_code) {
        echo "Skipping POS {$pos->name}: Branch has no account.\n";
        continue;
    }

    $parentCode = $pos->branch->account_code;
    $parentAccount = Account::where('code', $parentCode)->first();

    if (!$parentAccount) {
        echo "Skipping POS {$pos->name}: Parent account not found for code {$parentCode}.\n";
        continue;
    }

    $newCode = VoucherService::generateAccountCode($parentCode);

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
    echo "Created account {$account->code} for POS {$pos->name}\n";
}

echo "Done.\n";
