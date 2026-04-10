<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_entity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // e.g. 'branch', 'supplier', 'admin'
            $table->string('display_name', 100)->nullable();
            $table->timestamps();
        });

        // Seed common types
        DB::table('accounting_entity_types')->insert([
            ['name' => 'branch', 'display_name' => 'Branch'],
            ['name' => 'supplier', 'display_name' => 'Supplier'],
            ['name' => 'admin', 'display_name' => 'Admin'],
            ['name' => 'manager', 'display_name' => 'Manager'],
            ['name' => 'cashier', 'display_name' => 'Cashier'],
            ['name' => 'driver', 'display_name' => 'Driver'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entity_types');
    }
};
