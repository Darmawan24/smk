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
        Schema::create('nilai_p5', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignId('p5_id')->constrained('p5')->onDelete('cascade');
            $table->foreignId('dimensi_id')->constrained('dimensi_p5')->onDelete('cascade');
            $table->enum('nilai', ['MB', 'SB', 'BSH', 'SAB']);
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            $table->unique(['siswa_id', 'p5_id', 'dimensi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_p5');
    }
};