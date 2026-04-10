<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE accounting_scenarios MODIFY id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
        DB::statement('ALTER TABLE accounting_scenario_steps MODIFY id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
    }

    public function down(): void
    {
    }
};
