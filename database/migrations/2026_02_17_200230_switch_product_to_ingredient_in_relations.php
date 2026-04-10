<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // تحديث جدول Recipe Ingredients
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->foreignId('ingredient_id')->after('recipe_id')->constrained()->onDelete('cascade');
        });

        // تحديث جدول Purchase Invoice Items
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->foreignId('ingredient_id')->after('purchase_invoice_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn('ingredient_id');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
        });
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn('ingredient_id');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
        });
    }
};
