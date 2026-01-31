<?php

namespace App\Http\Controllers\Api\Siswa;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RaporSiswaController for Siswa
 *
 * Handles rapor belajar viewing for students.
 * Filter: tahun ajaran + semester (gabungan), periode (STS/SAS).
 * Tampilan: detail saja (tanpa download), nilai per periode, tanpa predikat.
 */
class RaporSiswaController extends Controller
{
    /**
     * Get rapor detail for authenticated student.
     * Query: tahun_ajaran_id (required), jenis (sts|sas).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'jenis' => 'required|in:sts,sas',
        ]);

        $user = Auth::user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        }

        $tahunAjaran = TahunAjaran::findOrFail($request->tahun_ajaran_id);
        $semester = (string) ($tahunAjaran->semester ?? '1');
        $jenis = strtolower($request->jenis);

        $waliKelasController = app(\App\Http\Controllers\Api\WaliKelas\CetakRaporBelajarController::class);
        $data = $waliKelasController->buildRaporDataPublic($siswa, $tahunAjaran->id, $semester, $jenis);

        $tahunInt = (int) $tahunAjaran->tahun;
        $periodeLabel = $jenis === 'sas' ? 'Akhir Semester (SAS)' : 'Tengah Semester (STS)';

        return response()->json([
            'tahun_ajaran' => [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
                'semester' => $tahunAjaran->semester,
                'label' => $tahunInt . '/' . ($tahunInt + 1) . ' - Semester ' . $semester,
            ],
            'periode' => $periodeLabel,
            'nilai_by_kelompok' => $data['nilai_by_kelompok'],
            'kehadiran' => $data['kehadiran'],
            'catatan_akademik' => $data['catatan_akademik'],
        ]);
    }
}
