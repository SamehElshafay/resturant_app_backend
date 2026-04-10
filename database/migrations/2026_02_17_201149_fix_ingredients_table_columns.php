<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // Check for existing columns before adding
            if (!Schema::hasColumn('ingredients', 'name_ar')) {
                $table->string('name_ar')->nullable();
            }
            if (!Schema::hasColumn('ingredients', 'name_en')) {
                $table->string('name_en')->nullable();
            }
            if (!Schema::hasColumn('ingredients', 'unit')) {
                $table->string('unit')->default('kg'); // kg, g, ltr, ml, piece
            }
            if (!Schema::hasColumn('ingredients', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('ingredients', 'stock_quantity')) {
                $table->decimal('stock_quantity', 10, 3)->default(0); // المخزون الحالي الإجمالي
            }
            if (!Schema::hasColumn('ingredients', 'min_stock_level')) {
                $table->decimal('min_stock_level', 10, 3)->default(10); // حد إعادة الطلب
            }
        });
    }

    public function down(): void
    {
        // Don't drop these as they might have been created
        // We can just add dropColumn if we are sure they were added by this migration
    }
};
