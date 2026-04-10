<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RecipeIngredient;

foreach (RecipeIngredient::all() as $ri) {
    echo "ID: {$ri->id} | Ingredient ID: {$ri->ingredient_id} | Created: {$ri->created_at}\n";
}
