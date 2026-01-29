<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrate users with role wali_kelas to guru (role wali_kelas removed).
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'wali_kelas')->update(['role' => 'guru']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely restore wali_kelas role without additional context
    }
};
