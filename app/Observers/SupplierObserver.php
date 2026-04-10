<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Supplier;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Log;

class SupplierObserver
{
    public function created(Supplier $supplier): void
    {
        try {
            $entityType = 'supplier';
            $code = VoucherService::generateAccountCode($entityType);

            if (!$code) {
                Log::info("SupplierObserver: No accounting config found for '{$entityType}'. Skipping.");
                return;
            }

            $parentCode = implode('-', array_slice(explode('-', $code), 0, -1));
            $parentAccount = Account::where('code', $parentCode)->first();

            $account = Account::create([
                'parent_id' => $parentAccount?->id,
                'name' => 'Supplier: ' . $supplier->name,
                'name_en' => 'Supplier: ' . ($supplier->name_en ?? $supplier->name),
                'name_ar' => 'مورد: ' . ($supplier->name_ar ?? $supplier->name),
                'code' => $code,
                'type' => 2, // Liability (Credits)
            ]);

            $supplier->update([
                'account_id' => $account->id,
                'account_code' => $account->code
            ]);

            Log::info("SupplierObserver: Created account {$code} for supplier #{$supplier->id}.");
        } catch (\Exception $e) {
            Log::error("SupplierObserver error: " . $e->getMessage());
        }
    }

    public function updated(Supplier $supplier): void
    {
        if ($supplier->wasChanged(['name', 'name_ar', 'name_en']) && $supplier->account_id) {
            $account = Account::find($supplier->account_id);
            if ($account) {
                $account->update([
                    'name' => 'Supplier: ' . $supplier->name,
                    'name_en' => 'Supplier: ' . ($supplier->name_en ?? $supplier->name),
                    'name_ar' => 'مورد: ' . ($supplier->name_ar ?? $supplier->name),
                ]);
                Log::info("SupplierObserver: Updated account name for supplier #{$supplier->id}.");
            }
        }
    }

    public function deleted(Supplier $supplier): void
    {
        if ($supplier->account_id) {
            Log::info("SupplierObserver: Supplier #{$supplier->id} deleted. Account #{$supplier->account_id} preserved for audit.");
        }
    }
}
