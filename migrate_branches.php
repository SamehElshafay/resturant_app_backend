<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\PosDevice;
use App\Models\Account;

$parent = Account::where('code', '002')->first();

$targetBranches = Branch::whereIn('name', ['ss3', 'Main Branch'])->get();

foreach ($targetBranches as $b) {
    if ($b->account_id) {
        $acc = Account::find($b->account_id);
        if ($acc && $acc->parent_id != $parent->id) {
            $acc->parent_id = $parent->id;
            $newCode = $acc->generateCode();

            $acc->update([
                'parent_id' => $parent->id,
                'code' => $newCode
            ]);
            $b->update(['account_code' => $newCode]);
            echo "Moved {$b->name} to {$newCode}\n";

            foreach ($b->posDevices as $pos) {
                if ($pos->account_id) {
                    $pAcc = Account::find($pos->account_id);
                    $pAcc->parent_id = $acc->id;
                    $pNewCode = $pAcc->generateCode();

                    $pAcc->update(['code' => $pNewCode, 'parent_id' => $acc->id]);
                    $pos->update(['account_code' => $pNewCode]);
                    echo "  - Updated POS {$pos->name} to {$pNewCode}\n";
                }
            }
        }
    }
}
echo "Done.\n";
