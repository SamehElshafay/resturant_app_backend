<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // order_number: human-readable daily counter (string to support offline prefix e.g. OFF_1)
            $table->string('order_number')->nullable()->after('id');
            // is_offline: true when the order was created while the device had no internet connection
            $table->boolean('is_offline')->default(false)->after('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_number', 'is_offline']);
        });
    }
};
