<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$prod = Product::find(3); // Burger
if ($prod && $prod->recipe) {
    echo "Recipe Ingredients for Product 3:\n";
    foreach ($prod->recipe->ingredients as $ri) {
        echo "ID: {$ri->id} | Ingredient ID: " . ($ri->ingredient_id ?? 'NULL') . " | Product ID: " . ($ri->child_product_id ?? 'NULL') . " | Qty: {$ri->quantity}\n";
    }
}
