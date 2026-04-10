<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
App\Models\Account::whereIn('id', [13, 14, 15])->delete();
echo "Cleaned up accounts 13, 14, 15.\n";
