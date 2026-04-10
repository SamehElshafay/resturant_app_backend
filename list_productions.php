<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Production;

echo "Recent Productions:\n";
foreach (Production::latest()->take(10)->get() as $p) {
    echo "ID: {$p->id} | Product ID: {$p->product_id} | Qty: {$p->quantity_produced} | Branch: {$p->branch_id} | Date: {$p->created_at}\n";
}
