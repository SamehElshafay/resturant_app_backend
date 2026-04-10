<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Check and update existing ones if necessary, but mostly adding new ones
            if (!Schema::hasColumn('vouchers', 'voucher_type')) {
                $table->string('voucher_type')->after('id')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'expense_id')) {
                $table->unsignedBigInteger('expense_id')->after('voucher_type')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'voucher_expense_type_id')) {
                $table->unsignedBigInteger('voucher_expense_type_id')->after('expense_id')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'expense_type')) {
                $table->string('expense_type')->after('voucher_expense_type_id')->nullable();
            }

            // amount already exists, but ensure it's decimal

            if (!Schema::hasColumn('vouchers', 'cash_amount')) {
                $table->decimal('cash_amount', 15, 2)->after('amount')->default(0);
            }
            if (!Schema::hasColumn('vouchers', 'bank_amount')) {
                $table->decimal('bank_amount', 15, 2)->after('cash_amount')->default(0);
            }
            if (!Schema::hasColumn('vouchers', 'account_code')) {
                $table->string('account_code')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'entity_type')) {
                $table->string('entity_type')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'recipient_account_code')) {
                $table->string('recipient_account_code')->nullable()->comment('كود حساب الطرف المستلم في حالة التحويل');
            }
            if (!Schema::hasColumn('vouchers', 'recipient_entity_type')) {
                $table->string('recipient_entity_type')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'treasury_account_code')) {
                $table->string('treasury_account_code')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'bank_account_code')) {
                $table->string('bank_account_code')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'note')) {
                $table->text('note')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'status')) {
                $table->string('status')->after('reference_number')->default('pending');
            }
            if (!Schema::hasColumn('vouchers', 'operation_code')) {
                $table->string('operation_code')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'transfer_status')) {
                $table->string('transfer_status')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'posted_by')) {
                $table->unsignedBigInteger('posted_by')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'posted_at')) {
                $table->timestamp('posted_at')->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'is_sttel')) {
                $table->boolean('is_sttel')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'voucher_type',
                'expense_id',
                'voucher_expense_type_id',
                'expense_type',
                'cash_amount',
                'bank_amount',
                'account_code',
                'entity_type',
                'recipient_account_code',
                'recipient_entity_type',
                'treasury_account_code',
                'bank_account_code',
                'note',
                'reference_number',
                'status',
                'operation_code',
                'transfer_status',
                'created_by',
                'posted_by',
                'posted_at',
                'is_sttel'
            ]);
        });
    }
};
