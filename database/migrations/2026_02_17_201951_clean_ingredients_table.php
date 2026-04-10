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
        Schema::table('ingredients', function (Blueprint $table) {
            // 1. Remove cost_per_unit which is causing the error
            if (Schema::hasColumn('ingredients', 'cost_per_unit')) {
                $table->dropColumn('cost_per_unit');
            }

            // 2. Remove other potential legacy columns
            $cols = ['description', 'image', 'category_id', 'barcode', 'sku', 'price'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('ingredients', $col)) {
                    // Try dropping foreign keys first if any
                    if (str_ends_with($col, '_id')) {
                        try {
                            $table->dropForeign([$col]);
                        } catch (\Exception $e) {
                        }
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration logic needed for cleanup
    }
};
