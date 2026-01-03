<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\NilaiPkl;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NilaiPklController extends Controller
{
    /**
     * Get siswa for PKL input (only for tingkat 12).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'wali_kelas') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get active classes where user is wali kelas
        $kelas = $user->kelasAsWali();
        
        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
            ], 404);
        }

        // Check if any kelas is tingkat 12
        $kelasTingkat12 = $kelas->filter(function($k) {
            return $k->tingkat == '12';
        });

        if ($kelasTingkat12->isEmpty()) {
            return response()->json([
                'message' => 'Kelas Anda tidak bisa menginputkan nilai PKL. Hanya wali kelas tingkat 12 yang dapat menginput nilai PKL.',
                'can_access' => false,
            ], 403);
        }

        // Get tahun ajaran aktif
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }
        
        // Get semester from tahun ajaran aktif
        $semester = $tahunAjaran->semester ?? '1';
        
        // Get kelas_id from request
        $kelasId = $request->has('kelas_id') && $request->kelas_id ? $request->kelas_id : null;

        // Filter kelas by kelas_id if provided, otherwise use all tingkat 12 classes
        $filteredKelas = $kelasTingkat12;
        if ($kelasId) {
            $filteredKelas = $kelasTingkat12->where('id', $kelasId);
            if ($filteredKelas->isEmpty()) {
                return response()->json([
                    'message' => 'Kelas tidak ditemukan atau Anda tidak memiliki akses ke kelas ini',
                ], 404);
            }
        }

        // Get all siswa from filtered classes
        $siswaIds = collect();
        foreach ($filteredKelas as $k) {
            $siswaIds = $siswaIds->merge($k->siswa()->where('status', 'aktif')->pluck('id'));
        }

        // Get siswa with their nilai PKL
        $siswa = \App\Models\Siswa::whereIn('id', $siswaIds->unique())
            ->with(['user', 'kelas' => function($q) {
                $q->with('jurusan');
            }])
            ->orderBy('nama_lengkap')
            ->get();

        // Get existing nilai PKL
        $nilaiPkl = NilaiPkl::whereIn('siswa_id', $siswaIds->unique())
            ->where('semester', $semester)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->get()
            ->keyBy('siswa_id');

        // Map siswa with nilai PKL
        $siswaData = $siswa->map(function($s) use ($nilaiPkl) {
            $nilai = $nilaiPkl->get($s->id);
            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'nisn' => $s->nisn,
                'nama_lengkap' => $s->nama_lengkap,
                'kelas' => [
                    'id' => $s->kelas->id,
                    'nama_kelas' => $s->kelas->nama_kelas,
                    'tingkat' => $s->kelas->tingkat,
                ],
                'nilai_pkl' => $nilai ? [
                    'id' => $nilai->id,
                    'nama_du_di' => $nilai->nama_du_di,
                    'lamanya_bulan' => $nilai->lamanya_bulan,
                    'keterangan' => $nilai->keterangan,
                ] : null,
            ];
        });

        return response()->json([
            'can_access' => true,
            'kelas' => $filteredKelas->map(function($k) {
                return [
                    'id' => $k->id,
                    'nama_kelas' => $k->nama_kelas,
                    'tingkat' => $k->tingkat,
                    'jurusan_id' => $k->jurusan_id,
                ];
            })->values(),
            'tahun_ajaran' => [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
                'semester' => $tahunAjaran->semester,
            ],
            'siswa' => $siswaData,
        ]);
    }

    /**
     * Store or update nilai PKL.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'wali_kelas') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get active classes where user is wali kelas
        $kelas = $user->kelasAsWali();
        
        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
            ], 404);
        }

        // Check if any kelas is tingkat 12
        $kelasTingkat12 = $kelas->filter(function($k) {
            return $k->tingkat == '12';
        });

        if ($kelasTingkat12->isEmpty()) {
            return response()->json([
                'message' => 'Kelas Anda tidak bisa menginputkan nilai PKL. Hanya wali kelas tingkat 12 yang dapat menginput nilai PKL.',
            ], 403);
        }

        $request->validate([
            'nilai_pkl' => 'required|array',
            'nilai_pkl.*.siswa_id' => 'required|exists:siswa,id',
            'nilai_pkl.*.kelas_id' => 'required|exists:kelas,id',
            'nilai_pkl.*.semester' => 'required|string|in:1,2',
            'nilai_pkl.*.nama_du_di' => 'nullable|string|max:255',
            'nilai_pkl.*.lamanya_bulan' => 'nullable|integer|min:1',
            'nilai_pkl.*.keterangan' => 'nullable|string|max:500',
        ]);

        // Get tahun ajaran aktif
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        // Verify that all siswa belong to user's tingkat 12 classes
        $kelasIds = $kelasTingkat12->pluck('id');
        foreach ($request->nilai_pkl as $nilaiData) {
            $siswa = \App\Models\Siswa::find($nilaiData['siswa_id']);
            if (!$siswa || !$kelasIds->contains($siswa->kelas_id) || $siswa->kelas->tingkat != '12') {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk menyimpan nilai PKL siswa ini',
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            $created = 0;
            $updated = 0;

            foreach ($request->nilai_pkl as $nilaiData) {
                $nilaiPkl = NilaiPkl::updateOrCreate(
                    [
                        'siswa_id' => $nilaiData['siswa_id'],
                        'semester' => $nilaiData['semester'],
                        'tahun_ajaran_id' => $tahunAjaran->id,
                    ],
                    [
                        'kelas_id' => $nilaiData['kelas_id'],
                        'nama_du_di' => $nilaiData['nama_du_di'] ?? null,
                        'lamanya_bulan' => $nilaiData['lamanya_bulan'] ?? null,
                        'keterangan' => $nilaiData['keterangan'] ?? null,
                    ]
                );

                if ($nilaiPkl->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Nilai PKL berhasil disimpan',
                'created' => $created,
                'updated' => $updated,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan nilai PKL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get PKL list by jurusan_id.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPklByJurusan(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'wali_kelas') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'jurusan_id' => 'required|integer|exists:jurusan,id',
        ]);

        // Get PKL by jurusan
        $pkl = \App\Models\Pkl::where('jurusan_id', $request->jurusan_id)
            ->with(['jurusan', 'pembimbingSekolah'])
            ->orderBy('nama_perusahaan')
            ->get();

        $pklData = $pkl->map(function($p) {
            return [
                'id' => $p->id,
                'nama_perusahaan' => $p->nama_perusahaan,
                'alamat_perusahaan' => $p->alamat_perusahaan,
                'pembimbing_perusahaan' => $p->pembimbing_perusahaan,
                'label' => $p->nama_perusahaan . ' - ' . $p->alamat_perusahaan,
            ];
        });

        return response()->json([
            'data' => $pklData,
        ]);
    }
}
