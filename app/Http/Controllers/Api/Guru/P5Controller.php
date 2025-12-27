<?php

namespace App\Http\Controllers\Api\Guru;

use App\Http\Controllers\Controller;
use App\Models\P5;
use App\Models\NilaiP5;
use App\Models\DimensiP5;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * P5Controller for Guru
 * 
 * Handles P5 project and nilai management for teachers
 */
class P5Controller extends Controller
{
    /**
     * Display a listing of P5 projects.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        $query = P5::with(['koordinator.user', 'tahunAjaran']);

        // If guru is koordinator, show only their projects
        if ($guru) {
            $query->where('koordinator_id', $guru->id);
        }

        if ($request->has('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        $p5 = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($p5);
    }

    /**
     * Store a newly created P5 project.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'tema' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
        ]);

        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        $p5 = P5::create([
            'tema' => $request->tema,
            'deskripsi' => $request->deskripsi,
            'koordinator_id' => $guru->id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
        ]);

        return response()->json([
            'message' => 'Projek P5 berhasil dibuat',
            'data' => $p5->load(['koordinator.user', 'tahunAjaran']),
        ], 201);
    }

    /**
     * Display the specified P5 project.
     *
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(P5 $p5)
    {
        $p5->load(['koordinator.user', 'tahunAjaran', 'nilaiP5.siswa.user', 'nilaiP5.dimensi']);

        return response()->json($p5);
    }

    /**
     * Update the specified P5 project.
     *
     * @param  Request  $request
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, P5 $p5)
    {
        $user = Auth::user();
        $guru = $user->guru;

        // Verify that this P5 belongs to the current guru
        if ($p5->koordinator_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah projek ini',
            ], 403);
        }

        $request->validate([
            'tema' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string',
        ]);

        $p5->update($request->only(['tema', 'deskripsi']));

        return response()->json([
            'message' => 'Projek P5 berhasil diperbarui',
            'data' => $p5->load(['koordinator.user', 'tahunAjaran']),
        ]);
    }

    /**
     * Remove the specified P5 project.
     *
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(P5 $p5)
    {
        $user = Auth::user();
        $guru = $user->guru;

        // Verify that this P5 belongs to the current guru
        if ($p5->koordinator_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menghapus projek ini',
            ], 403);
        }

        $p5->delete();

        return response()->json([
            'message' => 'Projek P5 berhasil dihapus',
        ]);
    }

    /**
     * Input nilai P5 for students.
     *
     * @param  Request  $request
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function inputNilai(Request $request, P5 $p5)
    {
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*.siswa_id' => 'required|exists:siswa,id',
            'nilai.*.dimensi_id' => 'required|exists:dimensi_p5,id',
            'nilai.*.nilai' => 'required|string|in:MB,SB,BSH,SAB',
            'nilai.*.catatan' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $guru = $user->guru;

        // Verify that this P5 belongs to the current guru
        if ($p5->koordinator_id !== $guru->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menginput nilai projek ini',
            ], 403);
        }

        DB::beginTransaction();
        try {
            foreach ($request->nilai as $nilaiData) {
                NilaiP5::updateOrCreate(
                    [
                        'siswa_id' => $nilaiData['siswa_id'],
                        'p5_id' => $p5->id,
                        'dimensi_id' => $nilaiData['dimensi_id'],
                    ],
                    [
                        'nilai' => $nilaiData['nilai'],
                        'catatan' => $nilaiData['catatan'] ?? null,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Nilai P5 berhasil diinput',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menginput nilai P5',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get nilai P5 for a project.
     *
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNilai(P5 $p5)
    {
        $nilai = $p5->nilaiP5()
                    ->with(['siswa.user', 'dimensi'])
                    ->get()
                    ->groupBy('siswa_id');

        $dimensi = DimensiP5::all();

        return response()->json([
            'p5' => $p5->load(['koordinator.user', 'tahunAjaran']),
            'dimensi' => $dimensi,
            'nilai' => $nilai,
        ]);
    }
}

