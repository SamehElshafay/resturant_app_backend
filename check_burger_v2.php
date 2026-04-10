<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\BranchProduct;

$p = Product::find(3);
if ($p) {
    echo "Product: {$p->name} | Central Stock: {$p->stock_quantity}\n";
    foreach (BranchProduct::where('product_id', 3)->get() as $bp) {
        echo "  - Branch: {$bp->branch->name} | Stock: {$bp->stock_quantity}\n";
    }
}
