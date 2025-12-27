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
}

