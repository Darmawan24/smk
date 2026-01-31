<?php

namespace App\Http\Controllers\Api\Siswa;

use App\Http\Controllers\Api\Concerns\BuildsRaporP5Data;
use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RaporP5SiswaController for Siswa
 *
 * Handles P5 rapor viewing for students.
 * View detail only (tanpa download).
 */
class RaporP5SiswaController extends Controller
{
    use BuildsRaporP5Data;

    /**
     * Get P5 rapor detail for authenticated student.
     * Query: tahun_ajaran_id (optional - filter by tahun ajaran).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        }

        $tahun = $request->tahun;
        $tahunAjaranId = $request->tahun_ajaran_id;
        if ($request->filled('tahun')) {
            $request->validate(['tahun' => 'required|numeric']);
        }
        if ($request->filled('tahun_ajaran_id')) {
            $request->validate(['tahun_ajaran_id' => 'exists:tahun_ajaran,id']);
        }

        $payload = $this->getDetailHasilP5Payload($request, $siswa);

        $tahunAjaran = null;
        if ($tahun !== null && $tahun !== '') {
            $tahunInt = (int) $tahun;
            $tahunAjaran = [
                'tahun' => $tahun,
                'label' => $tahunInt . '/' . ($tahunInt + 1),
            ];
        } elseif ($tahunAjaranId) {
            $ta = TahunAjaran::find($tahunAjaranId);
            if ($ta) {
                $tahunInt = (int) $ta->tahun;
                $tahunAjaran = [
                    'id' => $ta->id,
                    'tahun' => $ta->tahun,
                    'semester' => $ta->semester,
                    'label' => $tahunInt . '/' . ($tahunInt + 1),
                ];
            }
        }

        return response()->json([
            'siswa' => [
                'id' => $siswa->id,
                'nama_lengkap' => $siswa->nama_lengkap,
                'nis' => $siswa->nis,
                'kelas' => $siswa->kelas ? [
                    'nama_kelas' => $siswa->kelas->nama_kelas,
                    'jurusan' => $siswa->kelas->jurusan ? ['nama_jurusan' => $siswa->kelas->jurusan->nama_jurusan] : null,
                ] : null,
            ],
            'tahun_ajaran' => $tahunAjaran,
            'p5_projects' => $payload['p5_projects'] ?? [],
        ]);
    }
}
