<?php

namespace App\Services;

use App\Models\AccountingScenario;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AccountingEngine
{
    /**
     * Trigger an accounting scenario by its event key.
     *
     * @param string $eventKey
     * @param array $context Data variables for formulas and conditions
     * @param array $metadata Extra data for journal entry payload (reference_type, reference_id, description)
     * @return void
     */
    public static function trigger(string $eventKey, array $context, array $metadata = [])
    {
        $scenario = AccountingScenario::where('event_key', $eventKey)
            ->where('is_active', true)
            ->with([
                'steps' => function ($q) {
                    $q->where('is_active', true)->orderBy('priority');
                }
            ])
            ->first();

        if (!$scenario) {
            return; // Or log that scenario wasn't found
        }

        DB::transaction(function () use ($scenario, $context, $metadata) {
            $groupId = (string) Str::uuid();

            foreach ($scenario->steps as $step) {
                // 1. Evaluate Condition
                if (!self::evaluateCondition($step->condition_expression, $context)) {
                    continue;
                }

                // 2. Calculate Amount
                $formula = $step->debit_amount_formula ?: ($step->credit_amount_formula ?: $step->amount_formula);
                $amount = self::calculateFormula($formula, $context);
                if ($amount <= 0)
                    continue;

                // 3. Resolve Account Codes
                $debitCode = self::resolveTag($step->debit_account_pattern, $context);
                $creditCode = self::resolveTag($step->credit_account_pattern, $context);

                if (!$debitCode || !$creditCode) {
                    continue; // Skip if accounts can't be resolved
                }

                // 4. Record Journal Entries
                JournalEntry::create([
                    'transaction_group_id' => $groupId,
                    'debit_account_code' => $debitCode,
                    'credit_account_code' => null,
                    'debit' => $amount,
                    'credit' => null,
                    'reference_type' => $metadata['reference_type'] ?? 'scenario',
                    'reference_id' => $metadata['reference_id'] ?? null,
                    'description' => self::resolvePlaceholders($step->description ?? $scenario->name, $context),
                    'payload' => array_merge($context, ['scenario' => $scenario->event_key, 'step' => $step->id]),
                ]);

                JournalEntry::create([
                    'transaction_group_id' => $groupId,
                    'debit_account_code' => null,
                    'credit_account_code' => $creditCode,
                    'debit' => null,
                    'credit' => $amount,
                    'reference_type' => $metadata['reference_type'] ?? 'scenario',
                    'reference_id' => $metadata['reference_id'] ?? null,
                    'description' => self::resolvePlaceholders($step->description ?? $scenario->name, $context),
                    'payload' => array_merge($context, ['scenario' => $scenario->event_key, 'step' => $step->id]),
                ]);

                // 5. Propagate to parent accounts recursively
                self::propagateToParents($groupId, $debitCode, $creditCode, $amount, $scenario, $step, $context, $metadata);
            }
        });
    }

    /**
     * Propagate movement to all parent accounts recursively.
     */
    protected static function propagateToParents($groupId, $debitCode, $creditCode, $amount, $scenario, $step, $context, $metadata): void
    {
        $parentPayload = array_merge($context, [
            'scenario' => $scenario->event_key,
            'step' => $step->id,
            'is_parent_propagation' => true
        ]);

        // Propagate debit side up
        self::walkParents($debitCode, function (string $parentCode) use ($groupId, $amount, $scenario, $step, $parentPayload, $metadata) {
            JournalEntry::create([
                'transaction_group_id' => $groupId,
                'debit_account_code' => $parentCode,
                'credit_account_code' => null,
                'debit' => $amount,
                'credit' => null,
                'reference_type' => $metadata['reference_type'] ?? 'scenario',
                'reference_id' => $metadata['reference_id'] ?? null,
                'description' => self::resolvePlaceholders($step->description ?? $scenario->name, $parentPayload),
                'payload' => $parentPayload,
            ]);
        });

        // Propagate credit side up
        self::walkParents($creditCode, function (string $parentCode) use ($groupId, $amount, $scenario, $step, $parentPayload, $metadata) {
            JournalEntry::create([
                'transaction_group_id' => $groupId,
                'debit_account_code' => null,
                'credit_account_code' => $parentCode,
                'debit' => null,
                'credit' => $amount,
                'reference_type' => $metadata['reference_type'] ?? 'scenario',
                'reference_id' => $metadata['reference_id'] ?? null,
                'description' => self::resolvePlaceholders($step->description ?? $scenario->name, $parentPayload),
                'payload' => $parentPayload,
            ]);
        });
    }

    /**
     * Recursive walk through parents using JournalEntry::getParentCode.
     */
    protected static function walkParents(string $code, callable $callback): void
    {
        $parentCode = JournalEntry::getParentCode($code);
        while ($parentCode !== null) {
            $callback($parentCode);
            $parentCode = JournalEntry::getParentCode($parentCode);
        }
    }

    /**
     * Resolve tags like {{SUPPLIER}} or {{total}} in a string.
     */
    protected static function resolvePlaceholders(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($context) {
            $key = trim($matches[1]);
            return $context[$key] ?? $matches[0];
        }, $text);
    }

    /**
     * Resolve account tags specifically.
     */
    protected static function resolveTag(?string $pattern, array $context): ?string
    {
        if (empty($pattern))
            return null;

        // If it's already a code (numeric or XXX-YYY), return it
        if (preg_match('/^[0-9-]+$/', $pattern)) {
            return $pattern;
        }

        return self::resolvePlaceholders($pattern, $context);
    }

    /**
     * Basic formula calculator.
     */
    protected static function calculateFormula(?string $formula, array $context): float
    {
        if (empty($formula))
            return 0;

        $resolved = self::resolvePlaceholders($formula, $context);

        // Sanitize for basic math and common functions
        $resolved = preg_replace('/[^0-9\.\+\-\*\/\(\) absroundminmax,]/', '', $resolved);

        try {
            // Using a very basic eval here for math only. 
            // In production, use a math parser library.
            return (float) eval ("return ($resolved);");
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Basic condition evaluator.
     */
    protected static function evaluateCondition(?string $expression, array $context): bool
    {
        if (empty($expression))
            return true; // No condition means always run

        $resolved = self::resolvePlaceholders($expression, $context);

        // Handle basic boolean logic
        // Convert 'true' and 'false' strings to actual values for eval
        $resolved = str_replace(['true', 'false'], ['1', '0'], $resolved);

        // Sanitize: allow only comparison operators, numbers, periods, and logic operators
        $sanitized = preg_replace('/[^0-9\.\=\!\>\<\&\| ]/', '', $resolved);

        // Fix double equals if user only put one
        $sanitized = preg_replace('/(?<![\!\=\>\<])\=(?![\!\=\>\<])/', '==', $sanitized);

        try {
            return (bool) eval ("return ($sanitized);");
        } catch (Throwable $e) {
            return false;
        }
    }
}
