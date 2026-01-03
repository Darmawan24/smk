<?php

namespace App\Http\Controllers\Api\Guru;

use App\Http\Controllers\Controller;
use App\Models\NilaiEkstrakurikuler;
use App\Models\Ekstrakurikuler;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NilaiEkstrakurikulerController for Guru
 * 
 * Handles extracurricular grades management for teachers as pembina
 */
class NilaiEkstrakurikulerController extends Controller
{
    /**
     * Get siswa list for guru (for ekstrakurikuler management).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSiswa(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        $query = Siswa::with(['user', 'kelas.jurusan'])->where('status', 'aktif');

        // Filter by kelas_id if provided
        if ($request->has('kelas_id') && $request->kelas_id) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $siswa = $query->orderBy('nama_lengkap')->get();

        return response()->json($siswa);
    }

    /**
     * Get ekstrakurikuler owned by the current guru (as pembina).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myEkstrakurikuler(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        $ekstrakurikuler = Ekstrakurikuler::where('pembina_id', $guru->id)
                                         ->where('is_active', true)
                                         ->orderBy('nama')
                                         ->get();

        return response()->json($ekstrakurikuler);
    }

    /**
     * Get nilai ekstrakurikuler for a specific siswa.
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return \Illuminate\Http\JsonResponse
     */
    public function bySiswa(Siswa $siswa)
    {
        $tahunAjaranId = request()->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        $nilai = NilaiEkstrakurikuler::where('siswa_id', $siswa->id)
                                     ->where('tahun_ajaran_id', $tahunAjaranId)
                                     ->with(['ekstrakurikuler.pembina.user', 'tahunAjaran'])
                                     ->get();

        return response()->json([
            'siswa' => $siswa->load(['user', 'kelas.jurusan']),
            'nilai_ekstrakurikuler' => $nilai,
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
        ]);
    }

    /**
     * Store a newly created nilai ekstrakurikuler.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'ekstrakurikuler_id' => 'required|exists:ekstrakurikuler,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'predikat' => 'required|string|in:A,B,C,D',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $guru = $user->guru;

        // Verify that the ekstrakurikuler is supervised by the current guru
        $ekstrakurikuler = Ekstrakurikuler::find($request->ekstrakurikuler_id);
        if ($ekstrakurikuler->pembina_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menginput nilai ekstrakurikuler ini',
            ], 403);
        }

        // Check if nilai already exists
        $existing = NilaiEkstrakurikuler::where('siswa_id', $request->siswa_id)
                                        ->where('ekstrakurikuler_id', $request->ekstrakurikuler_id)
                                        ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
                                        ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Nilai ekstrakurikuler untuk siswa ini sudah ada',
            ], 422);
        }

        $nilai = NilaiEkstrakurikuler::create($request->only([
            'siswa_id',
            'ekstrakurikuler_id',
            'tahun_ajaran_id',
            'predikat',
            'keterangan',
        ]));

        return response()->json([
            'message' => 'Nilai ekstrakurikuler berhasil ditambahkan',
            'data' => $nilai->load(['siswa.user', 'ekstrakurikuler.pembina.user', 'tahunAjaran']),
        ], 201);
    }

    /**
     * Store multiple nilai ekstrakurikuler (batch).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchStore(Request $request)
    {
        $request->validate([
            'siswa_ids' => 'required|array|min:1',
            'siswa_ids.*' => 'required|exists:siswa,id',
            'ekstrakurikuler_id' => 'required|exists:ekstrakurikuler,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'predikat' => 'nullable|string|in:A,B,C,D',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        // Verify that the ekstrakurikuler is supervised by the current guru
        $ekstrakurikuler = Ekstrakurikuler::find($request->ekstrakurikuler_id);
        if ($ekstrakurikuler->pembina_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menginput nilai ekstrakurikuler ini',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $created = [];
            $skipped = [];

            foreach ($request->siswa_ids as $siswaId) {
                // Check if nilai already exists
                $existing = NilaiEkstrakurikuler::where('siswa_id', $siswaId)
                                                ->where('ekstrakurikuler_id', $request->ekstrakurikuler_id)
                                                ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
                                                ->first();

                if ($existing) {
                    $skipped[] = $siswaId;
                    continue;
                }

                $nilai = NilaiEkstrakurikuler::create([
                    'siswa_id' => $siswaId,
                    'ekstrakurikuler_id' => $request->ekstrakurikuler_id,
                    'tahun_ajaran_id' => $request->tahun_ajaran_id,
                    'predikat' => $request->predikat ?? 'C',
                    'keterangan' => $request->keterangan ?? null,
                ]);

                $created[] = $nilai->load(['siswa.user', 'ekstrakurikuler.pembina.user', 'tahunAjaran']);
            }

            DB::commit();

            $message = count($created) . ' siswa berhasil ditambahkan';
            if (count($skipped) > 0) {
                $message .= ', ' . count($skipped) . ' siswa sudah terdaftar sebelumnya';
            }

            return response()->json([
                'message' => $message,
                'data' => $created,
                'skipped_count' => count($skipped),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menambahkan siswa',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified nilai ekstrakurikuler.
     *
     * @param  Request  $request
     * @param  NilaiEkstrakurikuler  $nilaiEkstrakurikuler
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, NilaiEkstrakurikuler $nilaiEkstrakurikuler)
    {
        $user = Auth::user();
        $guru = $user->guru;

        // Verify that the ekstrakurikuler is supervised by the current guru
        if ($nilaiEkstrakurikuler->ekstrakurikuler->pembina_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah nilai ekstrakurikuler ini',
            ], 403);
        }

        $request->validate([
            'predikat' => 'sometimes|required|string|in:A,B,C,D',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $nilaiEkstrakurikuler->update($request->only([
            'predikat',
            'keterangan',
        ]));

        return response()->json([
            'message' => 'Nilai ekstrakurikuler berhasil diperbarui',
            'data' => $nilaiEkstrakurikuler->load(['siswa.user', 'ekstrakurikuler.pembina.user', 'tahunAjaran']),
        ]);
    }

    /**
     * Delete the specified nilai ekstrakurikuler.
     *
     * @param  NilaiEkstrakurikuler  $nilaiEkstrakurikuler
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(NilaiEkstrakurikuler $nilaiEkstrakurikuler)
    {
        $user = Auth::user();
        $guru = $user->guru;

        // Verify that the ekstrakurikuler is supervised by the current guru
        if ($nilaiEkstrakurikuler->ekstrakurikuler->pembina_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menghapus nilai ekstrakurikuler ini',
            ], 403);
        }

        try {
            $nilaiEkstrakurikuler->delete();
            return response()->json([
                'message' => 'Nilai ekstrakurikuler berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus nilai ekstrakurikuler',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

