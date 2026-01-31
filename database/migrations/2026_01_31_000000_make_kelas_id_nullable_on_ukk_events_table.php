<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->dropForeign(['kelas_id']);
            });
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->unsignedBigInteger('kelas_id')->nullable()->change();
            });
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
            });
        } else {
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->dropForeign(['kelas_id']);
            });
            DB::statement('ALTER TABLE ukk_events MODIFY kelas_id BIGINT UNSIGNED NULL');
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->dropForeign(['kelas_id']);
            });
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->unsignedBigInteger('kelas_id')->nullable(false)->change();
            });
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
            });
        } else {
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->dropForeign(['kelas_id']);
            });
            DB::statement('ALTER TABLE ukk_events MODIFY kelas_id BIGINT UNSIGNED NOT NULL');
            Schema::table('ukk_events', function (Blueprint $table) {
                $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
            });
        }
    }
};
