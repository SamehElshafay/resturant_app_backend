<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. journal_entries - Two lines per voucher (debit row + credit row)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_group_id', 50); // Groups the two lines of a single voucher
            $table->string('debit_account_code', 20)->nullable();
            $table->string('credit_account_code', 20)->nullable();
            $table->decimal('debit', 15, 2)->nullable();
            $table->decimal('credit', 15, 2)->nullable();
            $table->string('reference_type', 50)->nullable();   // e.g. 'voucher', 'order'
            $table->bigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('transaction_group_id');
            $table->index(['reference_type', 'reference_id']);
        });

        // 2. accounting_scenarios - Configurable rules for auto-generating journal entries
        Schema::create('accounting_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_key');
            $table->string('trigger_type')->default('ORDER_STATUS');
            $table->string('trigger_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('event_key');
        });

        // 3. accounting_scenario_steps - Steps for each scenario
        Schema::create('accounting_scenario_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('accounting_scenarios')->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('description')->nullable();
            $table->string('debit_account_pattern', 255)->nullable();
            $table->string('credit_account_pattern', 255)->nullable();
            $table->string('debit_amount_formula', 255)->nullable();
            $table->string('credit_amount_formula', 255)->nullable();
            $table->string('amount_formula', 255)->nullable();
            $table->string('condition_expression', 255)->nullable();
            $table->integer('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. accounting_entity_configs - Defines parent account code per entity type (user type, supplier, etc.)
        Schema::create('accounting_entity_configs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50); // e.g. 'admin', 'cashier', 'supplier', 'customer'
            $table->string('parent_account_code', 50); // e.g. '101'  - XXX in XXX-YYY
            $table->timestamps();

            $table->unique('entity_type');
        });

        // 5. accounting_voucher_routings - How to route accounts for each voucher type per entity
        Schema::create('accounting_voucher_routings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->string('voucher_type', 20);  // RECEIPT, PAYMENT, TRANSFER
            $table->string('parent_account_code', 50);
            $table->timestamps();

            $table->index(['entity_type', 'voucher_type']);
        });

        // 6. Drop old vouchers table and recreate with proper schema
        Schema::dropIfExists('vouchers');
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->enum('voucher_type', ['RECEIPT', 'PAYMENT', 'TRANSFER']);
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->unsignedBigInteger('voucher_expense_type_id')->nullable();
            $table->enum('expense_type', ['ADMINISTRATIVE', 'OPERATIONAL', 'NONE'])->default('NONE');
            $table->decimal('amount', 15, 2);
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->decimal('bank_amount', 15, 2)->default(0);
            $table->string('account_code', 20);                     // Account of the main party (debtor/creditor)
            $table->string('entity_type', 255)->nullable();
            $table->string('recipient_account_code', 20)->nullable()->comment('كود حساب الطرف المستلم في حالة التحويل');
            $table->string('recipient_entity_type', 255)->nullable();
            $table->string('treasury_account_code', 20)->nullable();
            $table->string('bank_account_code', 20)->nullable();
            $table->text('note')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['DRAFT', 'POSTED', 'CANCELLED'])->default('DRAFT');
            $table->string('operation_code', 20)->nullable();
            $table->enum('transfer_status', ['NONE', 'PENDING_ACCEPTANCE', 'ACCEPTED', 'REJECTED'])->default('NONE');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->boolean('is_sttel')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('voucher_type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_voucher_routings');
        Schema::dropIfExists('accounting_entity_configs');
        Schema::dropIfExists('accounting_scenario_steps');
        Schema::dropIfExists('accounting_scenarios');
        Schema::dropIfExists('journal_entries');
        // Restore simple vouchers
        Schema::dropIfExists('vouchers');
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('from_account_id');
            $table->unsignedBigInteger('to_account_id');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['receipt', 'payment', 'transfer']);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
};
