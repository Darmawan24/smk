<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

/**
 * CetakRaporController for Admin
 * 
 * Handles report card printing for admin
 */
class CetakRaporController extends Controller
{
    /**
     * Get rapor hasil belajar list.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasilBelajar(Request $request)
    {
        $query = Rapor::with([
            'siswa.user',
            'siswa.kelas.jurusan',
            'tahunAjaran',
            'approver'
        ]);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->has('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->has('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Only show approved or published rapor for printing
        if (!$request->has('status')) {
            $query->whereIn('status', ['approved', 'published']);
        }

        $rapor = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($rapor);
    }

    /**
     * Get detail rapor hasil belajar for printing.
     *
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailHasilBelajar(Rapor $rapor)
    {
        $rapor->load([
            'siswa.user',
            'siswa.kelas.jurusan',
            'tahunAjaran',
            'approver'
        ]);

        // Load nilai
        $rapor->nilai = $rapor->siswa->nilai()
            ->where('tahun_ajaran_id', $rapor->tahun_ajaran_id)
            ->with('mataPelajaran')
            ->get()
            ->groupBy('mataPelajaran.kelompok');

        // Load kehadiran
        $rapor->kehadiran = $rapor->siswa->kehadiran()
            ->where('tahun_ajaran_id', $rapor->tahun_ajaran_id)
            ->first();

        // Load catatan akademik
        $rapor->catatan_akademik = $rapor->siswa->catatanAkademik()
            ->where('tahun_ajaran_id', $rapor->tahun_ajaran_id)
            ->first();

        // Load nilai ekstrakurikuler
        $rapor->nilai_ekstrakurikuler = $rapor->siswa->nilaiEkstrakurikuler()
            ->where('tahun_ajaran_id', $rapor->tahun_ajaran_id)
            ->with('ekstrakurikuler')
            ->get();

        // Load nilai P5
        $rapor->nilai_p5 = $rapor->siswa->nilaiP5()
            ->with(['p5', 'dimensi'])
            ->get()
            ->groupBy('p5_id');

        return response()->json($rapor);
    }

    /**
     * Download rapor as PDF.
     *
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\Response
     */
    public function downloadHasilBelajar(Rapor $rapor)
    {
        // Verify rapor is approved or published
        if (!in_array($rapor->status, ['approved', 'published'])) {
            return response()->json([
                'message' => 'Rapor belum disetujui',
            ], 403);
        }

        // Load all necessary data
        $rapor->load([
            'siswa.user',
            'siswa.kelas.jurusan',
            'tahunAjaran',
            'approver'
        ]);

        // For now, return JSON response
        // TODO: Implement PDF generation using DomPDF or similar
        return response()->json([
            'message' => 'PDF generation will be implemented',
            'rapor' => $rapor,
        ]);

        // Future implementation:
        // $pdf = PDF::loadView('rapor.hasil-belajar', compact('rapor'));
        // return $pdf->download("rapor-{$rapor->siswa->nis}-{$rapor->tahunAjaran->tahun}.pdf");
    }

    /**
     * Preview rapor hasil belajar.
     *
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewHasilBelajar(Rapor $rapor)
    {
        return $this->detailHasilBelajar($rapor);
    }
}

