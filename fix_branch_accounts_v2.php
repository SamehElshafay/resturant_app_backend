<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Account;
use App\Models\Branch;

echo "Starting fix for branch accounts via account_code...\n";

$branches = Branch::all();
foreach ($branches as $branch) {
    if ($branch->account_code) {
        $account = Account::where('code', $branch->account_code)->first();
        if ($account) {
            $branchNameAr = $branch->name_ar ?: $branch->name;
            $branchNameEn = $branch->name_en ?: $branch->name;

            echo "Updating Account ID: {$account->id} (Code: {$account->code}) for Branch: {$branch->name}\n";
            $account->update([
                'name' => 'Branch: ' . $branchNameEn,
                'name_en' => 'Branch: ' . $branchNameEn,
                'name_ar' => 'فرع: ' . $branchNameAr,
            ]);

            // Also fix the account_id on branch if it's missing
            if (empty($branch->account_id)) {
                $branch->update(['account_id' => $account->id]);
            }
        }
    }
}

echo "Fix completed.\n";
