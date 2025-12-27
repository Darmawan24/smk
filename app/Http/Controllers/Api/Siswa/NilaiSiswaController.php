<?php

namespace App\Http\Controllers\Api\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Kehadiran;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * NilaiSiswaController for Siswa
 * 
 * Handles nilai viewing for students
 */
class NilaiSiswaController extends Controller
{
    /**
     * Display a listing of nilai for the authenticated student.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        }

        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        $query = Nilai::where('siswa_id', $siswa->id)
                     ->with(['mataPelajaran', 'guru.user', 'tahunAjaran']);

        if ($tahunAjaranId) {
            $query->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $nilai = $query->orderBy('mata_pelajaran_id')->get();

        return response()->json([
            'siswa' => $siswa->load(['user', 'kelas.jurusan']),
            'nilai' => $nilai,
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'rata_rata' => $nilai->avg('nilai_rapor') ?? 0,
        ]);
    }

    /**
     * Get nilai by tahun ajaran.
     *
     * @param  Request  $request
     * @param  TahunAjaran  $tahunAjaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function byTahunAjaran(Request $request, TahunAjaran $tahunAjaran)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        }

        $nilai = Nilai::where('siswa_id', $siswa->id)
                     ->where('tahun_ajaran_id', $tahunAjaran->id)
                     ->with(['mataPelajaran', 'guru.user'])
                     ->orderBy('mata_pelajaran_id')
                     ->get();

        return response()->json([
            'siswa' => $siswa->load(['user', 'kelas.jurusan']),
            'tahun_ajaran' => $tahunAjaran,
            'nilai' => $nilai,
            'rata_rata' => $nilai->avg('nilai_rapor') ?? 0,
        ]);
    }

    /**
     * Display the specified nilai.
     *
     * @param  Nilai  $nilai
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Nilai $nilai)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        // Verify that this nilai belongs to the authenticated student
        if ($nilai->siswa_id !== $siswa->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat nilai ini',
            ], 403);
        }

        $nilai->load(['siswa.user', 'siswa.kelas.jurusan', 'mataPelajaran', 'guru.user', 'tahunAjaran']);

        return response()->json($nilai);
    }

    /**
     * Get kehadiran for the authenticated student.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function kehadiran(Request $request)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        }

        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        $kehadiran = Kehadiran::where('siswa_id', $siswa->id)
                             ->where('tahun_ajaran_id', $tahunAjaranId)
                             ->with(['tahunAjaran'])
                             ->first();

        return response()->json([
            'siswa' => $siswa->load(['user', 'kelas.jurusan']),
            'kehadiran' => $kehadiran,
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
        ]);
    }
}

