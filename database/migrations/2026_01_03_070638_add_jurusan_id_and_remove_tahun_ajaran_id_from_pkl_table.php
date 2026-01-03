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
        Schema::table('pkl', function (Blueprint $table) {
            // Check if tahun_ajaran_id exists before dropping
            if (Schema::hasColumn('pkl', 'tahun_ajaran_id')) {
                // Drop foreign key first (if exists)
                try {
                    $table->dropForeign(['tahun_ajaran_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
                $table->dropColumn('tahun_ajaran_id');
            }
            
            // Add jurusan_id column as nullable first (for SQLite compatibility)
            if (!Schema::hasColumn('pkl', 'jurusan_id')) {
                $table->foreignId('jurusan_id')->nullable()->after('pembimbing_sekolah_id')->constrained('jurusan')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pkl', function (Blueprint $table) {
            // Drop foreign key and column for jurusan_id
            $table->dropForeign(['jurusan_id']);
            $table->dropColumn('jurusan_id');
            
            // Add back tahun_ajaran_id
            $table->foreignId('tahun_ajaran_id')->after('tanggal_selesai')->constrained('tahun_ajaran')->onDelete('cascade');
        });
    }
};
