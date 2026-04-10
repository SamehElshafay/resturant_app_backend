<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\BranchProduct;

$prods = Product::all();
foreach ($prods as $p) {
    echo "ID: {$p->id} | AR: {$p->name_ar} | EN: {$p->name_en}\n";
    $bps = BranchProduct::where('product_id', $p->id)->get();
    foreach ($bps as $bp) {
        $branchName = $bp->branch ? ($bp->branch->name_ar ?? $bp->branch->name) : 'N/A';
        echo "  - Branch: {$branchName} (ID: {$bp->branch_id}) | Stock: {$bp->stock_quantity}\n";
    }
}
