<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Production;

foreach ([3, 4, 5] as $id) {
    $p = Production::find($id);
    if ($p) {
        echo "ID: {$p->id} | Qty: {$p->quantity_produced} | Total Cost: {$p->total_cost}\n";
    }
}
