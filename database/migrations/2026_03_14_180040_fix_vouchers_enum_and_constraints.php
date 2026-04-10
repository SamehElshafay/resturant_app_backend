<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Fixes voucher_type, expense_type, status, transfer_status columns to match the target enum schema.
     * Also ensures account_code and created_by are NOT nullable.
     */
    public function up(): void
    {
        // Fix enum columns using raw SQL for maximum compatibility
        DB::statement("ALTER TABLE vouchers MODIFY voucher_type ENUM('RECEIPT','PAYMENT','TRANSFER') NOT NULL");
        DB::statement("ALTER TABLE vouchers MODIFY expense_type ENUM('ADMINISTRATIVE','OPERATIONAL','NONE') NOT NULL DEFAULT 'NONE'");
        DB::statement("ALTER TABLE vouchers MODIFY status ENUM('DRAFT','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT'");
        DB::statement("ALTER TABLE vouchers MODIFY transfer_status ENUM('NONE','PENDING_ACCEPTANCE','ACCEPTED','REJECTED') NOT NULL DEFAULT 'NONE'");
        DB::statement("ALTER TABLE vouchers MODIFY account_code VARCHAR(20) NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE vouchers MODIFY created_by INT(11) UNSIGNED NOT NULL DEFAULT 0");

        // Add indexes for performance
        DB::statement("ALTER TABLE vouchers ADD INDEX IF NOT EXISTS idx_status (status)");
        DB::statement("ALTER TABLE vouchers ADD INDEX IF NOT EXISTS idx_voucher_type (voucher_type)");
        DB::statement("ALTER TABLE vouchers ADD INDEX IF NOT EXISTS idx_created_by (created_by)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vouchers MODIFY voucher_type VARCHAR(50) NULL");
        DB::statement("ALTER TABLE vouchers MODIFY expense_type VARCHAR(50) NULL");
        DB::statement("ALTER TABLE vouchers MODIFY status VARCHAR(50) NULL");
        DB::statement("ALTER TABLE vouchers MODIFY transfer_status VARCHAR(50) NULL");
    }
};
