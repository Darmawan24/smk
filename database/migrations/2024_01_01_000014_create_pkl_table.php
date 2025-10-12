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
        Schema::create('pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->string('nama_perusahaan');
            $table->text('alamat_perusahaan');
            $table->string('pembimbing_perusahaan');
            $table->foreignId('pembimbing_sekolah_id')->constrained('guru')->onDelete('cascade');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('nilai_perusahaan', ['A', 'B', 'C', 'D'])->nullable();
            $table->enum('nilai_sekolah', ['A', 'B', 'C', 'D'])->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkl');
    }
};