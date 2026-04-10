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
            if (Schema::hasColumn('ingredients', 'name')) {
                // If we have data in 'name', maybe copy it to 'name_en' if name_en is empty?
                // For now, just drop as it's causing the issue and we are starting fresh or fixing structure.
                $table->dropColumn('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // Restore name if needed, nullable
            $table->string('name')->nullable();
        });
    }
};
