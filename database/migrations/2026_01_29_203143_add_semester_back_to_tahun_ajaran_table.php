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
        if (Schema::hasColumn('tahun_ajaran', 'semester')) {
            return;
        }

        Schema::table('tahun_ajaran', function (Blueprint $table) {
            $table->string('semester', 1)->default('1');
        });

        $rows = DB::table('tahun_ajaran')->orderBy('tahun')->orderBy('id')->get();
        $byTahun = $rows->groupBy('tahun');
        foreach ($byTahun as $tahun => $group) {
            $ids = $group->pluck('id')->values()->all();
            foreach ($ids as $i => $id) {
                $sem = ($i % 2) === 1 ? '2' : '1';
                DB::table('tahun_ajaran')->where('id', $id)->update(['semester' => $sem]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tahun_ajaran', function (Blueprint $table) {
            $table->dropColumn('semester');
        });
    }
};
