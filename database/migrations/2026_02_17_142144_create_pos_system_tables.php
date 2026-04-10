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
        // 1. Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('parent_account_id')->nullable(); // Root account for branch
            $table->timestamps();
        });

        // 2. Zones & Tables
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->enum('status', ['available', 'busy', 'reserved'])->default('available');
            $table->timestamps();
        });

        // 3. Bookings
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('restaurant_tables')->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->dateTime('booking_time');
            $table->enum('status', ['pending', 'confirmed', 'canceled', 'completed'])->default('pending');
            $table->timestamps();
        });

        // 4. Categories & Products
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('printer_ip')->nullable();
            $table->string('printer_connection_type')->default('network'); // network, usb, bluetooth
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->decimal('base_purchase_price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });

        Schema::create('product_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('modifier_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_modifier_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });

        // 5. Accounting (Chart of Accounts & Ledger)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code')->unique();
            $table->tinyInteger('type'); // 1: Asset, 2: Liability, 3: Equity, 4: Income, 5: Expense
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained(); // who created it
            $table->unsignedBigInteger('from_account_id');
            $table->unsignedBigInteger('to_account_id');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['receipt', 'payment', 'transfer']);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained(); // cashier/user responsible
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 6. POS Devices
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('connection_type')->default('network');
            $table->string('address')->nullable(); // IP or MAC
            $table->timestamps();
        });

        // 7. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('cashier_id')->constrained('users');
            $table->foreignId('pos_id')->nullable()->constrained('pos_devices');
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables');
            $table->integer('daily_number');
            $table->enum('type', ['dine_in', 'delivery', 'takeaway', 'booking']);
            $table->enum('status', ['pending', 'ongoing', 'completed', 'canceled'])->default('pending');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->decimal('item_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('modifier_option_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });

        // 8. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['pending', 'approved', 'canceled'])->default('pending');
            $table->date('invoice_date');
            $table->timestamps();
        });

        // 9. Ingredients (Recipe Management)
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('quantity', 15, 2);
            $table->string('unit'); // kg, piece, liter, etc.
            $table->decimal('cost_per_unit', 15, 2);
            $table->timestamps();
        });

        // 10. Documents
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('pos_devices');
        Schema::dropIfExists('ledgers');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('modifier_options');
        Schema::dropIfExists('product_modifiers');
        Schema::dropIfExists('branch_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('branches');
    }
};
