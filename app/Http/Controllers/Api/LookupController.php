<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Jurusan;
use App\Models\MataPelajaran;
use App\Models\Guru;
use App\Models\TahunAjaran;
use App\Models\DimensiP5;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LookupController
 * 
 * Provides lookup data for forms and dropdowns
 */
class LookupController extends Controller
{
    /**
     * Get all kelas for dropdown.
     */
    public function kelas()
    {
        $kelas = Kelas::with('jurusan')->get()->map(function ($kelas) {
            return [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
                'full_name' => $kelas->full_name,
            ];
        });

        return response()->json($kelas);
    }

    /**
     * Get all jurusan for dropdown.
     */
    public function jurusan()
    {
        $jurusan = Jurusan::select('id', 'kode_jurusan', 'nama_jurusan')->get();
        return response()->json($jurusan);
    }

    /**
     * Get all mata pelajaran for dropdown.
     * If user is guru or wali_kelas, only return mata pelajaran assigned to that guru.
     * If kelas_id is provided, filter by kelas_id to avoid duplicates.
     */
    public function mataPelajaran(Request $request)
    {
        $query = MataPelajaran::where('is_active', true)
            ->select('id', 'kode_mapel', 'nama_mapel', 'kelompok', 'kelas_id', 'kkm');

        // If user is guru or wali_kelas, filter by guru_id
        $user = Auth::user();
        if ($user && ($user->role === 'guru' || $user->role === 'wali_kelas') && $user->guru) {
            $query->where('guru_id', $user->guru->id);
        }

        // If kelas_id is provided, filter by kelas_id
        if ($request->has('kelas_id') && $request->kelas_id) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $mataPelajaran = $query->get();
        return response()->json($mataPelajaran);
    }

    /**
     * Get all guru for dropdown.
     * If user is guru or wali_kelas, return only current user's guru data.
     */
    public function guru()
    {
        $user = Auth::user();
        
        // If user is guru or wali_kelas, return only their guru data
        if ($user && ($user->role === 'guru' || $user->role === 'wali_kelas') && $user->guru) {
            return response()->json([
                'id' => $user->guru->id,
                'nuptk' => $user->guru->nuptk,
                'nama_lengkap' => $user->guru->nama_lengkap,
                'bidang_studi' => $user->guru->bidang_studi,
            ]);
        }
        
        // Otherwise, return all active guru
        $guru = Guru::with('user')->where('status', 'aktif')->get()->map(function ($guru) {
            return [
                'id' => $guru->id,
                'nuptk' => $guru->nuptk,
                'nama_lengkap' => $guru->nama_lengkap,
                'bidang_studi' => $guru->bidang_studi,
            ];
        });

        return response()->json($guru);
    }

    /**
     * Get all tahun ajaran for dropdown.
     */
    public function tahunAjaran()
    {
        $tahunAjaran = TahunAjaran::select('id', 'tahun', 'semester', 'is_active')->get();
        return response()->json($tahunAjaran);
    }

    /**
     * Get active tahun ajaran.
     */
    public function tahunAjaranAktif()
    {
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        return response()->json($tahunAjaran);
    }

    /**
     * Get all dimensi P5 for dropdown.
     */
    public function dimensiP5()
    {
        $dimensi = DimensiP5::select('id', 'nama_dimensi', 'deskripsi')->get();
        return response()->json($dimensi);
    }

    /**
     * Get kelas for a specific mata pelajaran.
     * 
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function kelasByMapel(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
        ]);

        $mataPelajaran = MataPelajaran::with(['kelas.jurusan'])->findOrFail($request->mata_pelajaran_id);

        // Verify that this mata pelajaran belongs to the current guru (if user is guru)
        $user = Auth::user();
        if ($user && $user->role === 'guru' && $user->guru) {
            if ($mataPelajaran->guru_id !== $user->guru->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk mata pelajaran ini',
                ], 403);
            }
        }

        $kelas = $mataPelajaran->kelas->map(function ($kelas) {
            return [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
                'full_name' => $kelas->full_name,
                'tingkat' => $kelas->tingkat,
            ];
        });

        return response()->json($kelas);
    }
}