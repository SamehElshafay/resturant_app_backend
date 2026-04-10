<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\User;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Log;

/**
 * UserObserver
 *
 * Auto-generates an Account and assigns an account_code to every new User
 * based on their role/entity type, using the accounting_entity_configs table.
 */
class UserObserver
{
    /**
     * Fires AFTER a user is created (so we have the ID and the role).
     */
    public function created(User $user): void
    {
        try {
            // Determine entity type (role name)
            $entityType = $user->roles->first()?->name ?? 'employee';

            // Generate the code using the service (reads accounting_entity_configs)
            $code = VoucherService::generateAccountCode($entityType);

            if (!$code) {
                Log::info("UserObserver: No accounting config found for entity type '{$entityType}'. Skipping account creation for user #{$user->id}.");
                return;
            }

            // Find the parent account
            $parentCode = implode('-', array_slice(explode('-', $code), 0, -1));
            $parentAccount = Account::where('code', $parentCode)->first();

            // Create the account in the chart of accounts
            $account = Account::create([
                'parent_id' => $parentAccount?->id,
                'name' => $user->name,
                'name_en' => $user->name,
                'name_ar' => $user->name,
                'code' => $code,
                'type' => 1, // Asset (receivable from employee)
            ]);

            // Link account back to user
            $user->update([
                'account_id' => $account->id,
                'account_code' => $account->code
            ]);

            Log::info("UserObserver: Created account {$code} for user #{$user->id} ({$user->name}).");
        } catch (\Exception $e) {
            Log::error("UserObserver: Failed to create account for user #{$user->id}: " . $e->getMessage());
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged(['name']) && $user->account_id) {
            $account = Account::find($user->account_id);
            if ($account) {
                $account->update([
                    'name' => $user->name,
                    'name_en' => $user->name,
                    'name_ar' => $user->name,
                ]);
                Log::info("UserObserver: Updated account name for user #{$user->id}.");
            }
        }
    }

    /**
     * When user is deleted, optionally de-activate the account (don't delete, audit trail).
     */
    public function deleted(User $user): void
    {
        if ($user->account_id) {
            // Just log; do NOT delete accounting records
            Log::info("UserObserver: User #{$user->id} deleted. Account #{$user->account_id} preserved for audit.");
        }
    }
}
