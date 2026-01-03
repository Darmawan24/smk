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
        Schema::create('nilai_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->string('semester', 1); // 1 or 2
            $table->string('nama_du_di')->nullable(); // Data Du/Di (Dunia Usaha/Dunia Industri)
            $table->integer('lamanya_bulan')->nullable(); // Duration in months
            $table->text('keterangan')->nullable();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint: one nilai_pkl per siswa per semester per tahun ajaran
            $table->unique(['siswa_id', 'semester', 'tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_pkl');
    }
};
