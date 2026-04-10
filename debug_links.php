<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Branch;
use App\Models\Account;

$branches = Branch::all();
foreach ($branches as $b) {
    echo "Branch #{$b->id}: Name='{$b->name}', AccountID='{$b->account_id}', AccountCode='{$b->account_code}', ParentAccountID='{$b->parent_account_id}'\n";
}

$accounts = Account::where('name', 'like', 'Branch:%')->get();
foreach ($accounts as $a) {
    echo "Account #{$a->id}: Name='{$a->name}', Code='{$a->code}'\n";
}
