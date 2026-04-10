<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;

$prod = Product::find(3); // Burger
if ($prod && $prod->recipe) {
    echo "Recipe for: {$prod->name}\n";
    foreach ($prod->recipe->ingredients as $ri) {
        $name = 'Unknown';
        $type = 'None';
        if ($ri->ingredient) {
            $name = $ri->ingredient->name;
            $type = 'Ingredient';
        } elseif ($ri->childProduct) {
            $name = $ri->childProduct->name;
            $type = 'Product';
        }
        echo " - Name: {$name} | Type: {$type} | Qty per piece: {$ri->quantity}\n";
    }
} else {
    echo "Product or Recipe not found.\n";
}
