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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_produced', 10, 2); // e.g. 80 meals
            $table->decimal('unit_cost', 10, 2); // Cost per meal at time of production
            $table->decimal('total_cost', 10, 2); // Total cost (80 * cost)
            $table->date('production_date');
            $table->unsignedBigInteger('performed_by')->nullable(); // User ID
            $table->timestamps();
        });

        // Add central stock to products table if not exists
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->decimal('stock_quantity', 10, 2)->default(0)->after('base_purchase_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
        });
    }
};
