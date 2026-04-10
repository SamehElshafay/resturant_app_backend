<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$p = Product::find(3); // Burger
if ($p) {
    $p->stock_quantity = 0;
    $p->save();
    echo "Fixed Product ID 3 (Burger) Central Stock to 0 as requested.\n";
}
