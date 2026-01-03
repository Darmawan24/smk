<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Kehadiran;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * KehadiranController for Wali Kelas
 * 
 * Handles attendance management for homeroom teachers
 */
class KehadiranController extends Controller
{
    /**
     * Get kehadiran for all students in wali kelas's classes.
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
        $kelas = $user->kelasAsWali();
        
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
        $filteredKelas = $kelas;
        if ($kelasId) {
            $filteredKelas = $kelas->where('id', $kelasId);
        }

        $siswaIds = collect();
        foreach ($filteredKelas as $k) {
            $siswaIds = $siswaIds->merge($k->siswa()->where('status', 'aktif')->pluck('id'));
        }

        $kehadiran = Kehadiran::whereIn('siswa_id', $siswaIds)
                              ->where('tahun_ajaran_id', $tahunAjaranId)
                              ->with(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran'])
                              ->get();

        // Create kehadiran for siswa that don't have one yet
        $siswaWithoutKehadiran = Siswa::whereIn('id', $siswaIds)
                                      ->whereNotIn('id', $kehadiran->pluck('siswa_id'))
                                      ->get();

        foreach ($siswaWithoutKehadiran as $s) {
            $kehadiran->push(Kehadiran::create([
                'siswa_id' => $s->id,
                'tahun_ajaran_id' => $tahunAjaranId,
                'sakit' => 0,
                'izin' => 0,
                'tanpa_keterangan' => 0,
            ])->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']));
        }

        // Format kelas data with jurusan (already loaded from kelasAsWali)
        $kelasData = $filteredKelas->map(function($k) {
            return [
                'id' => $k->id,
                'nama_kelas' => $k->nama_kelas,
                'tingkat' => $k->tingkat,
                'jurusan_id' => $k->jurusan_id,
                'jurusan' => $k->jurusan ? [
                    'id' => $k->jurusan->id,
                    'nama_jurusan' => $k->jurusan->nama_jurusan,
                ] : null,
            ];
        })->values();

        return response()->json([
            'kelas' => $kelasData,
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'kehadiran' => $kehadiran->sortBy('siswa.nama_lengkap')->values(),
        ]);
    }

    /**
     * Batch update kehadiran.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'kehadiran' => 'required|array',
            'kehadiran.*.id' => 'required|exists:kehadiran,id',
            'kehadiran.*.sakit' => 'nullable|integer|min:0',
            'kehadiran.*.izin' => 'nullable|integer|min:0',
            'kehadiran.*.tanpa_keterangan' => 'nullable|integer|min:0',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            foreach ($request->kehadiran as $kehadiranData) {
                $kehadiran = Kehadiran::find($kehadiranData['id']);

                // Verify that this kehadiran belongs to a student in wali kelas's class
                $isWaliKelas = $user->kelasAsWali()->contains('id', $kehadiran->siswa->kelas_id);
                if (!$isWaliKelas) {
                    continue;
                }

                $kehadiran->update([
                    'sakit' => $kehadiranData['sakit'] ?? 0,
                    'izin' => $kehadiranData['izin'] ?? 0,
                    'tanpa_keterangan' => $kehadiranData['tanpa_keterangan'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Kehadiran berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui kehadiran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific kehadiran.
     *
     * @param  Request  $request
     * @param  Kehadiran  $kehadiran
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Kehadiran $kehadiran)
    {
        $user = Auth::user();

        // Verify that this kehadiran belongs to a student in wali kelas's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $kehadiran->siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah kehadiran ini',
            ], 403);
        }

        $request->validate([
            'sakit' => 'nullable|integer|min:0',
            'izin' => 'nullable|integer|min:0',
            'tanpa_keterangan' => 'nullable|integer|min:0',
        ]);

        $kehadiran->update($request->only([
            'sakit',
            'izin',
            'tanpa_keterangan',
        ]));

        return response()->json([
            'message' => 'Kehadiran berhasil diperbarui',
            'data' => $kehadiran->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']),
        ]);
    }
}

