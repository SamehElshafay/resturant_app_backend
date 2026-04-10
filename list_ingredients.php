<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ingredient;

echo "Ingredients in DB:\n";
foreach (Ingredient::all() as $i) {
    echo "ID: {$i->id} | AR: {$i->name_ar} | EN: {$i->name_en} | Stock: {$i->stock_quantity}\n";
}
