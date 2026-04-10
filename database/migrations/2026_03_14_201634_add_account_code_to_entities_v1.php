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
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_code', 20)->nullable()->after('account_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('account_code', 20)->nullable()->after('phone');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('account_code', 20)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_code');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('account_code');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('account_code');
        });
    }
};
