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
        Schema::table('capaian_pembelajaran', function (Blueprint $table) {
            $table->string('semester', 1)->nullable()->after('fase');
            $table->string('tingkat', 10)->nullable()->after('semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capaian_pembelajaran', function (Blueprint $table) {
            $table->dropColumn(['semester', 'tingkat']);
        });
    }
};
