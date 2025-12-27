<?php

namespace App\Http\Controllers\Api\Guru;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NilaiController for Guru
 * 
 * Handles nilai sumatif management for teachers
 */
class NilaiController extends Controller
{
    /**
     * Get nilai for a specific kelas and mata pelajaran.
     *
     * @param  Request  $request
     * @param  Kelas  $kelas
     * @param  MataPelajaran  $mataPelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, Kelas $kelas, MataPelajaran $mataPelajaran)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        $siswa = $kelas->siswa()->where('status', 'aktif')->get();

        $nilai = Nilai::where('mata_pelajaran_id', $mataPelajaran->id)
                     ->where('tahun_ajaran_id', $tahunAjaranId)
                     ->where('guru_id', $guru->id)
                     ->whereIn('siswa_id', $siswa->pluck('id'))
                     ->with(['siswa.user', 'mataPelajaran'])
                     ->get();

        // Create nilai for siswa that don't have one yet
        $siswaWithoutNilai = $siswa->filter(function ($s) use ($nilai) {
            return !$nilai->contains('siswa_id', $s->id);
        });

        foreach ($siswaWithoutNilai as $s) {
            $nilai->push(Nilai::create([
                'siswa_id' => $s->id,
                'mata_pelajaran_id' => $mataPelajaran->id,
                'tahun_ajaran_id' => $tahunAjaranId,
                'guru_id' => $guru->id,
            ])->load(['siswa.user', 'mataPelajaran']));
        }

        return response()->json([
            'kelas' => $kelas->load('jurusan'),
            'mata_pelajaran' => $mataPelajaran,
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'nilai' => $nilai->sortBy('siswa.nama_lengkap')->values(),
        ]);
    }

    /**
     * Batch update nilai.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*.id' => 'required|exists:nilai,id',
            'nilai.*.nilai_sumatif_1' => 'nullable|integer|min:0|max:100',
            'nilai.*.nilai_sumatif_2' => 'nullable|integer|min:0|max:100',
            'nilai.*.nilai_sumatif_3' => 'nullable|integer|min:0|max:100',
            'nilai.*.nilai_sumatif_4' => 'nullable|integer|min:0|max:100',
            'nilai.*.nilai_sumatif_5' => 'nullable|integer|min:0|max:100',
            'nilai.*.nilai_uts' => 'nullable|integer|min:0|max:100',
            'nilai.*.nilai_uas' => 'nullable|integer|min:0|max:100',
            'nilai.*.deskripsi' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $guru = $user->guru;

        DB::beginTransaction();
        try {
            foreach ($request->nilai as $nilaiData) {
                $nilai = Nilai::find($nilaiData['id']);

                // Verify that this nilai belongs to the current guru
                if ($nilai->guru_id !== $guru->id) {
                    continue;
                }

                $nilai->update([
                    'nilai_sumatif_1' => $nilaiData['nilai_sumatif_1'] ?? null,
                    'nilai_sumatif_2' => $nilaiData['nilai_sumatif_2'] ?? null,
                    'nilai_sumatif_3' => $nilaiData['nilai_sumatif_3'] ?? null,
                    'nilai_sumatif_4' => $nilaiData['nilai_sumatif_4'] ?? null,
                    'nilai_sumatif_5' => $nilaiData['nilai_sumatif_5'] ?? null,
                    'nilai_uts' => $nilaiData['nilai_uts'] ?? null,
                    'nilai_uas' => $nilaiData['nilai_uas'] ?? null,
                    'deskripsi' => $nilaiData['deskripsi'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Nilai berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui nilai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific nilai.
     *
     * @param  Request  $request
     * @param  Nilai  $nilai
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Nilai $nilai)
    {
        $user = Auth::user();
        $guru = $user->guru;

        // Verify that this nilai belongs to the current guru
        if ($nilai->guru_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah nilai ini',
            ], 403);
        }

        $request->validate([
            'nilai_sumatif_1' => 'nullable|integer|min:0|max:100',
            'nilai_sumatif_2' => 'nullable|integer|min:0|max:100',
            'nilai_sumatif_3' => 'nullable|integer|min:0|max:100',
            'nilai_sumatif_4' => 'nullable|integer|min:0|max:100',
            'nilai_sumatif_5' => 'nullable|integer|min:0|max:100',
            'nilai_uts' => 'nullable|integer|min:0|max:100',
            'nilai_uas' => 'nullable|integer|min:0|max:100',
            'deskripsi' => 'nullable|string|max:500',
        ]);

        $nilai->update($request->only([
            'nilai_sumatif_1',
            'nilai_sumatif_2',
            'nilai_sumatif_3',
            'nilai_sumatif_4',
            'nilai_sumatif_5',
            'nilai_uts',
            'nilai_uas',
            'deskripsi',
        ]));

        return response()->json([
            'message' => 'Nilai berhasil diperbarui',
            'data' => $nilai->load(['siswa.user', 'mataPelajaran', 'tahunAjaran']),
        ]);
    }
}

