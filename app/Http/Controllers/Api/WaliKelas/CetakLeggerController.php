<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Api\Concerns\BuildsLeggerData;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cetak Legger untuk Wali Kelas.
 * Sama dengan Admin cetak legger, tetapi hanya untuk kelas yang diwalikan.
 */
class CetakLeggerController extends Controller
{
    use BuildsLeggerData;

    /**
     * Get legger (grade book) for a class (hanya kelas wali).
     *
     * @param  Request  $request
     * @param  Kelas  $kelas
     * @return \Illuminate\Http\JsonResponse
     */
    public function legger(Request $request, Kelas $kelas)
    {
        $kelasIds = Auth::user()->kelasAsWali()->pluck('id')->toArray();
        if (! in_array((int) $kelas->id, $kelasIds)) {
            return response()->json(['message' => 'Kelas bukan kelas yang Anda walikan'], 403);
        }

        $payload = $this->getLeggerPayload($request, $kelas);
        if (isset($payload['message'])) {
            return response()->json($payload, 404);
        }

        return response()->json($payload);
    }

    /**
     * Download legger as Excel (.xlsx) (hanya kelas wali).
     *
     * @param  Request  $request
     * @param  Kelas  $kelas
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadLegger(Request $request, Kelas $kelas)
    {
        $kelasIds = Auth::user()->kelasAsWali()->pluck('id')->toArray();
        if (! in_array((int) $kelas->id, $kelasIds)) {
            return response()->json(['message' => 'Kelas bukan kelas yang Anda walikan'], 403);
        }

        $payload = $this->getLeggerPayload($request, $kelas);
        if (isset($payload['message'])) {
            return response()->json($payload, 404);
        }

        return $this->downloadLeggerAsExcel($payload, $kelas);
    }
}
