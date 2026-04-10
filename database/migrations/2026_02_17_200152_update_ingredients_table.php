<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ingredients')) {
            Schema::create('ingredients', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar')->nullable();
                $table->string('name_en')->nullable();
                $table->string('unit')->default('kg'); // kg, g, ltr, ml, piece
                $table->decimal('cost_price', 10, 2)->default(0);
                $table->decimal('stock_quantity', 10, 3)->default(0); // المخزون الحالي الإجمالي
                $table->decimal('min_stock_level', 10, 3)->default(10); // حد إعادة الطلب
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
