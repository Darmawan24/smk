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
        Schema::table('nilai', function (Blueprint $table) {
            $table->foreignId('capaian_pembelajaran_id')->nullable()->after('mata_pelajaran_id')->constrained('capaian_pembelajaran')->onDelete('cascade');
            $table->string('semester', 1)->nullable()->after('capaian_pembelajaran_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai', function (Blueprint $table) {
            $table->dropForeign(['capaian_pembelajaran_id']);
            $table->dropColumn(['capaian_pembelajaran_id', 'semester']);
        });
    }
};
