<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * NilaiKelasController for Wali Kelas
 * 
 * Handles nilai monitoring for homeroom teachers
 */
class NilaiKelasController extends Controller
{
    /**
     * Get nilai for all students in wali kelas's classes.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        // Get active classes where user is wali kelas
        $kelas = $user->kelasAsWali;
        
        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
            ], 404);
        }

        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        $kelasId = $request->get('kelas_id');
        if ($kelasId) {
            $kelas = $kelas->where('id', $kelasId);
        }

        $siswaIds = collect();
        foreach ($kelas as $k) {
            $siswaIds = $siswaIds->merge($k->siswa()->where('status', 'aktif')->pluck('id'));
        }

        $nilai = Nilai::whereIn('siswa_id', $siswaIds)
                     ->where('tahun_ajaran_id', $tahunAjaranId)
                     ->with(['siswa.user', 'siswa.kelas.jurusan', 'mataPelajaran', 'guru.user'])
                     ->get()
                     ->groupBy('siswa_id');

        return response()->json([
            'kelas' => $kelas->load('jurusan'),
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'nilai' => $nilai,
        ]);
    }

    /**
     * Get rekap nilai for kelas.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rekap(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        $kelasId = $request->get('kelas_id');
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$kelasId || !$tahunAjaranId) {
            return response()->json([
                'message' => 'Kelas dan tahun ajaran harus dipilih',
            ], 422);
        }

        $kelas = Kelas::find($kelasId);
        
        // Verify that user is wali kelas for this class
        $isWaliKelas = $user->kelasAsWali->contains('id', $kelasId);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda bukan wali kelas untuk kelas ini',
            ], 403);
        }

        $siswa = $kelas->siswa()->where('status', 'aktif')->get();
        $nilai = Nilai::whereIn('siswa_id', $siswa->pluck('id'))
                     ->where('tahun_ajaran_id', $tahunAjaranId)
                     ->with(['siswa.user', 'mataPelajaran'])
                     ->get();

        // Calculate statistics
        $rekap = [];
        foreach ($siswa as $s) {
            $siswaNilai = $nilai->where('siswa_id', $s->id);
            $rekap[] = [
                'siswa' => $s->load('user'),
                'total_mapel' => $siswaNilai->count(),
                'rata_rata' => $siswaNilai->avg('nilai_rapor') ?? 0,
                'nilai' => $siswaNilai->values(),
            ];
        }

        return response()->json([
            'kelas' => $kelas->load('jurusan'),
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'rekap' => $rekap,
        ]);
    }

    /**
     * Get nilai for a specific siswa.
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return \Illuminate\Http\JsonResponse
     */
    public function bySiswa(Request $request, Siswa $siswa)
    {
        $user = Auth::user();
        
        // Verify that user is wali kelas for this student's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda bukan wali kelas untuk siswa ini',
            ], 403);
        }

        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        $nilai = Nilai::where('siswa_id', $siswa->id)
                     ->where('tahun_ajaran_id', $tahunAjaranId)
                     ->with(['mataPelajaran', 'guru.user', 'tahunAjaran'])
                     ->get();

        return response()->json([
            'siswa' => $siswa->load(['user', 'kelas.jurusan']),
            'nilai' => $nilai,
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
        ]);
    }
}

