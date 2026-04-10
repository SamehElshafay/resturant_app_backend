<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('ingredients', 'product_id')) {
                // Drop foreign key if exists, then drop column
                // We use generic array notation based on table_column_foreign
                try {
                    $table->dropForeign(['product_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist
                }
                $table->dropColumn('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // We won't add it back as we are moving away from dependency
            // Or we could make it nullable for safety if rolling back
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
        });
    }
};
