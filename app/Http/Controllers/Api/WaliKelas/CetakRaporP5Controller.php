<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Api\Concerns\BuildsRaporP5Data;
use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Cetak Rapor P5 untuk Wali Kelas.
 * Sama dengan Admin cetak rapor P5, tetapi hanya siswa di kelas yang diwalikan.
 */
class CetakRaporP5Controller extends Controller
{
    use BuildsRaporP5Data;

    /**
     * Daftar siswa yang punya nilai P5 (hanya kelas wali).
     */
    public function hasilP5(Request $request)
    {
        $user = Auth::user();
        $kelasIds = $user->kelasAsWali()->pluck('id')->toArray();

        if (empty($kelasIds)) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
                'from' => null,
                'to' => null,
            ]);
        }

        $query = Siswa::with(['user', 'kelas.jurusan'])
            ->whereIn('kelas_id', $kelasIds)
            ->whereHas('nilaiP5');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kelas_id') && in_array((int) $request->kelas_id, $kelasIds)) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('tahun_ajaran_id') && $request->tahun_ajaran_id) {
            $query->whereHas('nilaiP5', function ($q) use ($request) {
                $q->whereHas('p5', function ($qp) use ($request) {
                    $qp->where('tahun_ajaran_id', $request->tahun_ajaran_id);
                });
            });
        }

        $siswa = $query->orderBy('nama_lengkap')->paginate($request->get('per_page', 15));

        $tahunAjaranId = $request->tahun_ajaran_id;
        $siswa->getCollection()->transform(function ($item) use ($tahunAjaranId) {
            $nilaiP5Query = $item->nilaiP5()->with(['p5.tahunAjaran', 'dimensi']);
            if ($tahunAjaranId) {
                $nilaiP5Query->whereHas('p5', function ($q) use ($tahunAjaranId) {
                    $q->where('tahun_ajaran_id', $tahunAjaranId);
                });
            }
            $nilaiP5 = $nilaiP5Query->get();
            $item->p5_projects = $nilaiP5->groupBy('p5_id')->map(function ($nilai) {
                $p5 = $nilai->first()->p5;

                return [
                    'id' => $p5->id,
                    'tema' => $p5->tema,
                    'tahun_ajaran' => $p5->tahunAjaran
                        ? "{$p5->tahunAjaran->tahun} - Semester {$p5->tahunAjaran->semester}"
                        : null,
                    'dimensi_count' => $nilai->count(),
                ];
            })->values();
            $item->total_p5_projects = $item->p5_projects->count();

            return $item;
        });

        return response()->json($siswa);
    }

    /**
     * Detail rapor P5 untuk satu siswa (hanya jika siswa di kelas wali).
     */
    public function detailHasilP5(Request $request, Siswa $siswa)
    {
        $kelasIds = Auth::user()->kelasAsWali()->pluck('id')->toArray();
        if (! in_array((int) $siswa->kelas_id, $kelasIds)) {
            return response()->json(['message' => 'Siswa bukan di kelas yang Anda walikan'], 403);
        }

        $payload = $this->getDetailHasilP5Payload($request, $siswa);

        return response()->json($payload);
    }

    /**
     * Preview rapor P5 (sama response dengan detail).
     */
    public function previewHasilP5(Request $request, Siswa $siswa)
    {
        return $this->detailHasilP5($request, $siswa);
    }

    /**
     * Download rapor P5 PDF (sama view dan format dengan admin).
     */
    public function downloadHasilP5(Request $request, Siswa $siswa)
    {
        $kelasIds = Auth::user()->kelasAsWali()->pluck('id')->toArray();
        if (! in_array((int) $siswa->kelas_id, $kelasIds)) {
            return response()->json(['message' => 'Siswa bukan di kelas yang Anda walikan'], 403);
        }

        $siswa->load(['kelas.jurusan']);
        $payload = $this->detailHasilP5($request, $siswa)->getData(true);
        $data = $this->buildRaporP5PdfData($payload, $siswa);
        $data['siswa'] = $siswa;

        $pdf = Pdf::loadView('rapor.hasil-p5', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'rapor-p5-' . preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $siswa->nis) . '.pdf';

        return $pdf->download($filename);
    }
}
