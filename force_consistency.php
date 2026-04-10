<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ingredient;
use App\Models\PurchaseInvoiceItem;

$consumptions = [
    2 => 2000, // Burger meat
    3 => 2000, // Kaiser bread
    4 => 20    // Cheese
];

foreach ($consumptions as $id => $qty) {
    $ing = Ingredient::find($id);
    if ($ing) {
        echo "Deducting {$qty} from Ingredient {$id} ({$ing->name_ar})...\n";

        // Use the new method I just added
        $ing->deductStock($qty);

        // Force table stock to 0 just in case there was any drift
        $ing->stock_quantity = 0;
        $ing->save();

        echo " - New Table Stock: {$ing->stock_quantity}\n";
        $invoiceSum = PurchaseInvoiceItem::where('ingredient_id', $id)
            ->whereHas('purchaseInvoice', fn($q) => $q->where('status', 'approved'))
            ->sum('remaining_quantity');
        echo " - New Invoice Residual Sum: {$invoiceSum}\n";
    }
}
