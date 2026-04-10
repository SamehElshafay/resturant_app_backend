<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\PosDevice;
use App\Models\Account;

$branches = Branch::all();
foreach ($branches as $b) {
    echo "ID: {$b->id} | Name: {$b->name} | Code: {$b->account_code} | AccountID: {$b->account_id}\n";
    $pos = PosDevice::where('branch_id', $b->id)->get();
    foreach ($pos as $p) {
        echo "  - POS: {$p->name} | Code: {$p->account_code} | AccountID: {$p->account_id}\n";
    }
}
