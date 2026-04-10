<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$p = Product::find(3); // Burger
if ($p) {
    // Set central stock to 0 because those 1000 were meant for Giza branch only
    $p->stock_quantity = 0;
    $p->save();
    echo "Corrected Burger Central Stock to 0 (Items are in branches).\n";
}
