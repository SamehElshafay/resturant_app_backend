<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseInvoiceItem;
use App\Models\Ingredient;

echo "Ingredient Stock vs Invoices:\n";
foreach (Ingredient::all() as $i) {
    $invoiceSum = PurchaseInvoiceItem::where('ingredient_id', $i->id)
        ->whereHas('purchaseInvoice', fn($q) => $q->where('status', 'approved'))
        ->sum('remaining_quantity');
    echo "ID: {$i->id} | Name: {$i->name_ar} | Stock Table: {$i->stock_quantity} | Invoice Sum: {$invoiceSum}\n";
}
