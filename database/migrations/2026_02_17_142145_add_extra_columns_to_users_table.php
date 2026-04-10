<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('pos_id')->nullable()->constrained('pos_devices');
            $table->unsignedBigInteger('account_id')->nullable(); // Personal account
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('image')->nullable();
            $table->decimal('salary', 15, 2)->default(0);
            $table->integer('reward_points')->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['pos_id']);
            $table->dropColumn(['branch_id', 'pos_id', 'account_id', 'phone', 'address', 'image', 'salary', 'reward_points', 'commission_rate', 'is_active']);
        });
    }
};
