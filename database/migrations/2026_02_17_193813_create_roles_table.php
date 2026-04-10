<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // تحديث roles table
        if (!Schema::hasColumn('roles', 'display_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('display_name')->after('name')->nullable();
                $table->text('description')->nullable();
            });
        }

        // إنشاء permissions إذا لم يكن موجود
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->string('group')->default('general');
                $table->timestamps();
            });
        }

        // إنشاء role_permission pivot
        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->foreignid('permission_id')->constrained()->onDelete('cascade');
                $table->primary(['role_id', 'permission_id']);
            });
        }

        // إضافة role_id للـ users إذا لم يكن موجود
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('branch_id')->constrained()->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
    }
};
