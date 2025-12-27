<?php

namespace App\Http\Controllers\Api\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pkl;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PklController for Guru
 * 
 * Handles PKL (Praktik Kerja Lapangan) management for teachers as pembimbing
 */
class PklController extends Controller
{
    /**
     * Get PKL records where the current guru is supervising.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myStudents(Request $request)
    {
        $user = Auth::user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json([
                'message' => 'Guru tidak ditemukan',
            ], 404);
        }

        // Since pembimbing_sekolah is a string, filter by guru name
        $pkl = Pkl::where('pembimbing_sekolah', 'like', "%{$guru->nama_lengkap}%")
                  ->with(['tahunAjaran'])
                  ->orderBy('created_at', 'desc')
                  ->get();

        return response()->json([
            'pkl' => $pkl,
            'total' => $pkl->count(),
        ]);
    }

    /**
     * Update PKL record (note: nilai fields were removed in migration).
     *
     * @param  Request  $request
     * @param  Pkl  $pkl
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNilaiSekolah(Request $request, Pkl $pkl)
    {
        $user = Auth::user();
        $guru = $user->guru;

        // Verify that this PKL is supervised by the current guru
        if (stripos($pkl->pembimbing_sekolah, $guru->nama_lengkap) === false) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengubah PKL ini',
            ], 403);
        }

        $request->validate([
            'pembimbing_sekolah' => 'sometimes|string|max:255',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $pkl->update($request->only([
            'pembimbing_sekolah',
            'keterangan',
        ]));

        return response()->json([
            'message' => 'PKL berhasil diperbarui',
            'data' => $pkl->load(['tahunAjaran']),
        ]);
    }
}

