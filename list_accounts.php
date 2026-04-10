<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;

$accounts = Account::all();
foreach ($accounts as $a) {
    echo "{$a->code}: {$a->name}\n";
}
