<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Branch;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Log;

class BranchObserver
{
    public function created(Branch $branch): void
    {
        try {
            $entityType = 'branch';
            $code = VoucherService::generateAccountCode($entityType);

            if (!$code) {
                Log::info("BranchObserver: No accounting config found for '{$entityType}'. Skipping.");
                return;
            }

            $parentCode = implode('-', array_slice(explode('-', $code), 0, -1));
            $parentAccount = Account::where('code', $parentCode)->first();

            $branchNameAr = $branch->name_ar ?: $branch->name;
            $branchNameEn = $branch->name_en ?: $branch->name;

            $account = Account::create([
                'parent_id' => $parentAccount?->id,
                'name' => 'Branch: ' . $branchNameEn,
                'name_en' => 'Branch: ' . $branchNameEn,
                'name_ar' => 'فرع: ' . $branchNameAr,
                'code' => $code,
                'type' => 1, // Asset? Or Capital? Usually Branch is an entity. Let's use Expense/Asset parent.
            ]);

            $branch->update([
                'account_id' => $account->id,
                'account_code' => $account->code
            ]);

            Log::info("BranchObserver: Created account {$code} for branch #{$branch->id}.");
        } catch (\Exception $e) {
            Log::error("BranchObserver error: " . $e->getMessage());
        }
    }

    public function updated(Branch $branch): void
    {
        if ($branch->wasChanged(['name', 'name_ar', 'name_en']) && $branch->account_id) {
            $account = Account::find($branch->account_id);
            if ($account) {
                $branchNameAr = $branch->name_ar ?: $branch->name;
                $branchNameEn = $branch->name_en ?: $branch->name;

                $account->update([
                    'name' => 'Branch: ' . $branchNameEn,
                    'name_en' => 'Branch: ' . $branchNameEn,
                    'name_ar' => 'فرع: ' . $branchNameAr,
                ]);
                Log::info("BranchObserver: Updated account name for branch #{$branch->id}.");
            }
        }
    }

    public function deleted(Branch $branch): void
    {
        if ($branch->account_id) {
            Log::info("BranchObserver: Branch #{$branch->id} deleted. Account #{$branch->account_id} preserved for audit.");
        }
    }
}
