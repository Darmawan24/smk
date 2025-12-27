<?php

namespace App\Http\Controllers\Api\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

/**
 * RekapController for Kepala Sekolah
 * 
 * Handles reports and statistics for principal
 */
class RekapController extends Controller
{
    /**
     * Get nilai rekap.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nilai(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        $query = Nilai::where('tahun_ajaran_id', $tahunAjaranId)
                     ->with(['siswa.kelas.jurusan', 'mataPelajaran']);

        if ($request->has('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        if ($request->has('jurusan_id')) {
            $query->whereHas('siswa.kelas', function ($q) use ($request) {
                $q->where('jurusan_id', $request->jurusan_id);
            });
        }

        $nilai = $query->get();

        // Calculate statistics
        $statistik = [
            'total_siswa' => $nilai->pluck('siswa_id')->unique()->count(),
            'total_mapel' => $nilai->pluck('mata_pelajaran_id')->unique()->count(),
            'rata_rata_umum' => $nilai->avg('nilai_rapor') ?? 0,
            'nilai_per_kelas' => $nilai->groupBy('siswa.kelas_id')->map(function ($nilaiKelas) {
                return [
                    'kelas' => $nilaiKelas->first()->siswa->kelas->nama_kelas ?? '-',
                    'rata_rata' => $nilaiKelas->avg('nilai_rapor') ?? 0,
                    'total_siswa' => $nilaiKelas->pluck('siswa_id')->unique()->count(),
                ];
            })->values(),
        ];

        return response()->json([
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'statistik' => $statistik,
            'data' => $nilai->paginate($request->get('per_page', 15)),
        ]);
    }

    /**
     * Get kehadiran rekap.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function kehadiran(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        $query = Kehadiran::where('tahun_ajaran_id', $tahunAjaranId)
                          ->with(['siswa.kelas.jurusan']);

        if ($request->has('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        $kehadiran = $query->get();

        // Calculate statistics
        $statistik = [
            'total_siswa' => $kehadiran->count(),
            'total_sakit' => $kehadiran->sum('sakit'),
            'total_izin' => $kehadiran->sum('izin'),
            'total_tanpa_keterangan' => $kehadiran->sum('tanpa_keterangan'),
            'kehadiran_per_kelas' => $kehadiran->groupBy('siswa.kelas_id')->map(function ($kehadiranKelas) {
                return [
                    'kelas' => $kehadiranKelas->first()->siswa->kelas->nama_kelas ?? '-',
                    'total_siswa' => $kehadiranKelas->count(),
                    'total_sakit' => $kehadiranKelas->sum('sakit'),
                    'total_izin' => $kehadiranKelas->sum('izin'),
                    'total_tanpa_keterangan' => $kehadiranKelas->sum('tanpa_keterangan'),
                ];
            })->values(),
        ];

        return response()->json([
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'statistik' => $statistik,
            'data' => $kehadiran->paginate($request->get('per_page', 15)),
        ]);
    }

    /**
     * Get prestasi rekap.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function prestasi(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        // Get top students by nilai rapor
        $topSiswa = Nilai::where('tahun_ajaran_id', $tahunAjaranId)
                        ->selectRaw('siswa_id, AVG(nilai_rapor) as rata_rata')
                        ->groupBy('siswa_id')
                        ->orderBy('rata_rata', 'desc')
                        ->limit(10)
                        ->with(['siswa.user', 'siswa.kelas.jurusan'])
                        ->get();

        return response()->json([
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'top_siswa' => $topSiswa,
        ]);
    }

    /**
     * Get general statistics.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistik(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        $statistik = [
            'total_kelas' => Kelas::count(),
            'total_siswa' => \App\Models\Siswa::where('status', 'aktif')->count(),
            'total_guru' => \App\Models\Guru::where('status', 'aktif')->count(),
            'total_mapel' => \App\Models\MataPelajaran::where('is_active', true)->count(),
            'rata_rata_nilai' => Nilai::where('tahun_ajaran_id', $tahunAjaranId)->avg('nilai_rapor') ?? 0,
            'total_rapor_approved' => \App\Models\Rapor::where('tahun_ajaran_id', $tahunAjaranId)
                                                         ->where('status', 'approved')
                                                         ->count(),
            'total_rapor_pending' => \App\Models\Rapor::where('tahun_ajaran_id', $tahunAjaranId)
                                                       ->where('status', 'pending')
                                                       ->count(),
        ];

        return response()->json([
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'statistik' => $statistik,
        ]);
    }

    /**
     * Get legger for a specific kelas.
     *
     * @param  Request  $request
     * @param  Kelas  $kelas
     * @return \Illuminate\Http\JsonResponse
     */
    public function leggerKelas(Request $request, Kelas $kelas)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?? TahunAjaran::where('is_active', true)->first()?->id;

        if (!$tahunAjaranId) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        $siswa = $kelas->siswa()->where('status', 'aktif')->get();
        $nilai = Nilai::whereIn('siswa_id', $siswa->pluck('id'))
                     ->where('tahun_ajaran_id', $tahunAjaranId)
                     ->with(['siswa.user', 'mataPelajaran'])
                     ->get()
                     ->groupBy(['siswa_id', 'mata_pelajaran_id']);

        $legger = [];
        foreach ($siswa as $s) {
            $siswaNilai = $nilai->get($s->id, collect());
            $legger[] = [
                'siswa' => $s->load('user'),
                'nilai' => $siswaNilai->map(function ($nilaiMapel) {
                    return $nilaiMapel->first();
                })->values(),
            ];
        }

        return response()->json([
            'kelas' => $kelas->load('jurusan'),
            'tahun_ajaran' => TahunAjaran::find($tahunAjaranId),
            'legger' => $legger,
        ]);
    }

    /**
     * Download legger as PDF.
     *
     * @param  Request  $request
     * @param  Kelas  $kelas
     * @return \Illuminate\Http\Response
     */
    public function downloadLegger(Request $request, Kelas $kelas)
    {
        // TODO: Implement PDF generation using DomPDF
        // For now, return the data
        return $this->leggerKelas($request, $kelas);
    }
}

