<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\BranchProduct;

$prod = Product::where('name_ar', 'LIKE', '%برجر%')->first();
if ($prod) {
    echo "Product: {$prod->name_ar} (ID: {$prod->id})\n";
    $bps = BranchProduct::where('product_id', $prod->id)->get();
    foreach ($bps as $bp) {
        $branchName = $bp->branch ? ($bp->branch->name_ar ?? $bp->branch->name) : 'N/A';
        echo "Branch: {$branchName} (ID: {$bp->branch_id}) | Stock: {$bp->stock_quantity}\n";
    }
} else {
    echo "Product not found\n";
}
