<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\PosDevice;
use Illuminate\Support\Facades\Log;

class PosDeviceObserver
{
    public function created(PosDevice $posDevice): void
    {
        try {
            $branch = $posDevice->branch;
            if (!$branch || !$branch->account_code) {
                Log::info("PosDeviceObserver: Branch or Branch Account Code missing for POS #{$posDevice->id}. Skipping.");
                return;
            }

            $parentCode = $branch->account_code;

            // Find the next sequential child number under this branch parent
            $existing = Account::where('code', 'like', "{$parentCode}-%")
                ->orderByRaw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) DESC")
                ->first();

            $nextSeq = 1;
            if ($existing) {
                $parts = explode('-', $existing->code);
                $lastSeg = (int) end($parts);
                $nextSeq = $lastSeg + 1;
            }

            $newCode = $parentCode . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

            $account = Account::create([
                'parent_id' => $branch->account_id,
                'branch_id' => $branch->id,
                'name' => 'POS: ' . $posDevice->name,
                'name_en' => 'POS: ' . $posDevice->name,
                'name_ar' => 'نقطة بيع: ' . $posDevice->name,
                'code' => $newCode,
                'type' => 1, // Asset (Cash Drawer)
            ]);

            $posDevice->update([
                'account_id' => $account->id,
                'account_code' => $account->code
            ]);

            Log::info("PosDeviceObserver: Created account {$newCode} for POS #{$posDevice->id}.");
        } catch (\Exception $e) {
            Log::error("PosDeviceObserver error: " . $e->getMessage());
        }
    }
}
