<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\CatatanAkademik;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CatatanAkademikController for Wali Kelas
 * 
 * Handles academic notes management for homeroom teachers
 */
class CatatanAkademikController extends Controller
{
    /**
     * List siswa dengan catatan akademik untuk filter kelas (halaman Catatan Wali Kelas).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listByKelas(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isWaliKelas()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $kelas = $user->kelasAsWali();
        if ($kelas->isEmpty()) {
            return response()->json([
                'kelas' => [],
                'tahun_ajaran' => null,
                'siswa' => [],
            ], 200);
        }

        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        if (!$tahunAjaran) {
            return response()->json([
                'kelas' => $kelas->map(fn ($k) => ['id' => $k->id, 'nama_kelas' => $k->nama_kelas]),
                'tahun_ajaran' => null,
                'siswa' => [],
            ], 200);
        }

        $kelasId = $request->get('kelas_id');
        $filteredKelas = $kelasId ? $kelas->where('id', $kelasId) : $kelas;

        $kelasData = $kelas->map(fn ($k) => [
            'id' => $k->id,
            'nama_kelas' => $k->nama_kelas,
        ])->values();

        if ($filteredKelas->isEmpty() || !$kelasId) {
            return response()->json([
                'kelas' => $kelasData,
                'tahun_ajaran' => $tahunAjaran,
                'siswa' => [],
            ], 200);
        }

        $siswaIds = $filteredKelas->first()->siswa()->where('status', 'aktif')->pluck('id');
        $catatanMap = CatatanAkademik::whereIn('siswa_id', $siswaIds)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('wali_kelas_id', $user->id)
            ->get()
            ->keyBy('siswa_id');

        $siswa = Siswa::whereIn('id', $siswaIds)
            ->with(['kelas.jurusan'])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function ($s) use ($catatanMap) {
                $ca = $catatanMap->get($s->id);
                return [
                    'id' => $s->id,
                    'nis' => $s->nis,
                    'nisn' => $s->nisn,
                    'nama_lengkap' => $s->nama_lengkap,
                    'kelas' => $s->kelas ? ['id' => $s->kelas->id, 'nama_kelas' => $s->kelas->nama_kelas] : null,
                    'catatan_akademik' => $ca ? ['id' => $ca->id, 'catatan' => $ca->catatan] : null,
                ];
            });

        return response()->json([
            'kelas' => $kelasData,
            'tahun_ajaran' => $tahunAjaran,
            'siswa' => $siswa,
        ], 200);
    }

    /**
     * Batch update catatan akademik (create or update per siswa).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'catatan' => 'required|array',
            'catatan.*.siswa_id' => 'required|exists:siswa,id',
            'catatan.*.catatan' => 'nullable|string',
        ]);

        $user = Auth::user();
        $kelasIds = $user->kelasAsWali()->pluck('id');

        DB::beginTransaction();
        try {
            foreach ($request->catatan as $item) {
                $siswa = Siswa::find($item['siswa_id']);
                if (!$siswa || !$kelasIds->contains($siswa->kelas_id)) {
                    continue;
                }

                $catatanText = trim($item['catatan'] ?? '');
                $existing = CatatanAkademik::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
                    ->first();

                if ($existing) {
                    $existing->update(['catatan' => $catatanText]);
                } elseif ($catatanText !== '') {
                    CatatanAkademik::create([
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $request->tahun_ajaran_id,
                        'wali_kelas_id' => $user->id,
                        'catatan' => $catatanText,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Catatan wali kelas berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of catatan akademik.
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

        $siswaIds = collect();
        foreach ($kelas as $k) {
            $siswaIds = $siswaIds->merge($k->siswa()->where('status', 'aktif')->pluck('id'));
        }

        $query = CatatanAkademik::whereIn('siswa_id', $siswaIds)
                                ->where('wali_kelas_id', $user->id)
                                ->with(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']);

        if ($tahunAjaranId) {
            $query->where('tahun_ajaran_id', $tahunAjaranId);
        }

        if ($request->has('siswa_id')) {
            $query->where('siswa_id', $request->siswa_id);
        }

        $catatan = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($catatan);
    }

    /**
     * Store a newly created catatan akademik.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'catatan' => 'required|string',
        ]);

        $user = Auth::user();
        $siswa = Siswa::find($request->siswa_id);

        // Verify that user is wali kelas for this student's class
        $isWaliKelas = $user->kelasAsWali()->contains('id', $siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda bukan wali kelas untuk siswa ini',
            ], 403);
        }

        // Check if catatan already exists for this siswa and tahun ajaran
        $existing = CatatanAkademik::where('siswa_id', $request->siswa_id)
                                   ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
                                   ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Catatan akademik untuk siswa ini pada tahun ajaran ini sudah ada',
            ], 422);
        }

        $catatan = CatatanAkademik::create([
            'siswa_id' => $request->siswa_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'wali_kelas_id' => $user->id,
            'catatan' => $request->catatan,
        ]);

        return response()->json([
            'message' => 'Catatan akademik berhasil ditambahkan',
            'data' => $catatan->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']),
        ], 201);
    }

    /**
     * Display the specified catatan akademik.
     *
     * @param  CatatanAkademik  $catatanAkademik
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(CatatanAkademik $catatanAkademik)
    {
        $user = Auth::user();

        // Verify that this catatan belongs to the current wali kelas
        if ($catatanAkademik->wali_kelas_id !== $user->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat catatan ini',
            ], 403);
        }

        $catatanAkademik->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']);

        return response()->json($catatanAkademik);
    }

    /**
     * Update the specified catatan akademik.
     *
     * @param  Request  $request
     * @param  CatatanAkademik  $catatanAkademik
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, CatatanAkademik $catatanAkademik)
    {
        $user = Auth::user();

        // Verify that this catatan belongs to the current wali kelas
        if ($catatanAkademik->wali_kelas_id !== $user->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah catatan ini',
            ], 403);
        }

        $request->validate([
            'catatan' => 'required|string',
        ]);

        $catatanAkademik->update([
            'catatan' => $request->catatan,
        ]);

        return response()->json([
            'message' => 'Catatan akademik berhasil diperbarui',
            'data' => $catatanAkademik->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']),
        ]);
    }

    /**
     * Remove the specified catatan akademik.
     *
     * @param  CatatanAkademik  $catatanAkademik
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CatatanAkademik $catatanAkademik)
    {
        $user = Auth::user();

        // Verify that this catatan belongs to the current wali kelas
        if ($catatanAkademik->wali_kelas_id !== $user->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menghapus catatan ini',
            ], 403);
        }

        $catatanAkademik->delete();

        return response()->json([
            'message' => 'Catatan akademik berhasil dihapus',
        ]);
    }

    /**
     * Get catatan akademik for a specific siswa.
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return \Illuminate\Http\JsonResponse
     */
    public function bySiswa(Siswa $siswa)
    {
        $user = Auth::user();

        // Verify that user is wali kelas for this student's class
        $isWaliKelas = $user->kelasAsWali()->contains('id', $siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda bukan wali kelas untuk siswa ini',
            ], 403);
        }

        $catatan = CatatanAkademik::where('siswa_id', $siswa->id)
                                  ->where('wali_kelas_id', $user->id)
                                  ->with(['tahunAjaran'])
                                  ->orderBy('created_at', 'desc')
                                  ->get();

        return response()->json([
            'siswa' => $siswa->load(['user', 'kelas.jurusan']),
            'catatan' => $catatan,
        ]);
    }
}

