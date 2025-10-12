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
        Schema::create('tujuan_pembelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_pembelajaran_id')->constrained('capaian_pembelajaran')->onDelete('cascade');
            $table->string('kode_tp');
            $table->text('deskripsi');
            $table->timestamps();
            
            $table->unique(['kode_tp', 'capaian_pembelajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tujuan_pembelajaran');
    }
};