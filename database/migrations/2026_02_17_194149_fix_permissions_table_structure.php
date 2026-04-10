<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Roles Table
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'name')) {
                    $table->string('name')->unique();
                }
                if (!Schema::hasColumn('roles', 'display_name')) {
                    $table->string('display_name')->after('name')->nullable();
                }
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->after('display_name')->nullable();
                }
            });
        }

        // 2. Permissions Table
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'name')) {
                    $table->string('name')->unique();
                }
                if (!Schema::hasColumn('permissions', 'display_name')) {
                    $table->string('display_name')->after('name')->nullable();
                }
                if (!Schema::hasColumn('permissions', 'group')) {
                    $table->string('group')->default('general')->after('display_name');
                }
            });
        }
    }

    public function down(): void
    {
    }
};
