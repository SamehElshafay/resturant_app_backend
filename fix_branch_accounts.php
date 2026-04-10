<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Account;
use App\Models\Branch;

echo "Starting fix for branch accounts...\n";

$branches = Branch::all();
foreach ($branches as $branch) {
    if ($branch->account_id) {
        $account = Account::find($branch->account_id);
        if ($account) {
            $branchNameAr = $branch->name_ar ?: $branch->name;
            $branchNameEn = $branch->name_en ?: $branch->name;

            echo "Updating Account ID: {$account->id} for Branch: {$branch->name}\n";
            $account->update([
                'name' => 'Branch: ' . $branchNameEn,
                'name_en' => 'Branch: ' . $branchNameEn,
                'name_ar' => 'فرع: ' . $branchNameAr,
            ]);
        }
    }
}

echo "Fix completed.\n";
