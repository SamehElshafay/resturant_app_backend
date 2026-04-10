<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\PosDevice;
use App\Models\Account;

echo "--- ACCOUNTS ---\n";
$accs = Account::orderBy('code')->get();
foreach ($accs as $a) {
    echo "{$a->id} | {$a->code} | {$a->name}\n";
}

echo "\n--- BRANCHES ---\n";
$branches = Branch::all();
foreach ($branches as $b) {
    echo "ID: {$b->id} | Name: {$b->name} | Code: {$b->account_code} | AccountID: {$b->account_id}\n";
}

echo "\n--- POS ---\n";
$pos = PosDevice::all();
foreach ($pos as $p) {
    echo "ID: {$p->id} | Name: {$p->name} | Code: {$p->account_code} | AccountID: {$p->account_id} | BranchID: {$p->branch_id}\n";
}
