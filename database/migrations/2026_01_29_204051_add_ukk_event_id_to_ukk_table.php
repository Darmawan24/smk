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
        if (Schema::hasColumn('ukk', 'ukk_event_id')) {
            return;
        }

        Schema::table('ukk', function (Blueprint $table) {
            $table->foreignId('ukk_event_id')->nullable()->after('id')->constrained('ukk_events')->onDelete('cascade');
        });

        $rows = DB::table('ukk')->get();
        $key = fn ($r) => implode('|', [
            $r->tahun_ajaran_id,
            $r->jurusan_id,
            $r->kelas_id,
            $r->nama_du_di ?? '',
            $r->tanggal_ujian,
            $r->penguji_internal_id,
            $r->penguji_eksternal ?? '',
        ]);
        $seen = [];
        foreach ($rows as $r) {
            $k = $key($r);
            if (!isset($seen[$k])) {
                $id = DB::table('ukk_events')->insertGetId([
                    'tahun_ajaran_id' => $r->tahun_ajaran_id,
                    'jurusan_id' => $r->jurusan_id,
                    'kelas_id' => $r->kelas_id,
                    'nama_du_di' => $r->nama_du_di,
                    'tanggal_ujian' => $r->tanggal_ujian,
                    'penguji_internal_id' => $r->penguji_internal_id,
                    'penguji_eksternal' => $r->penguji_eksternal,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ]);
                $seen[$k] = $id;
            }
            DB::table('ukk')->where('id', $r->id)->update(['ukk_event_id' => $seen[$k]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ukk', function (Blueprint $table) {
            $table->dropForeign(['ukk_event_id']);
        });
    }
};
