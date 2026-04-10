<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Account;
use App\Models\Branch;

$accounts = Account::where('name', 'like', 'Branch:%')->get();
foreach ($accounts as $acc) {
    echo "ID: {$acc->id} | Name: '{$acc->name}' | EN: '{$acc->name_en}' | AR: '{$acc->name_ar}'\n";
}

$branches = Branch::all();
foreach ($branches as $b) {
    echo "Branch ID: {$b->id} | Name: '{$b->name}' | EN: '{$b->name_en}' | AR: '{$b->name_ar}'\n";
}
