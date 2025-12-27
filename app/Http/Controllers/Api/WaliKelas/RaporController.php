<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * RaporController for Wali Kelas
 * 
 * Handles report card generation and management for homeroom teachers
 */
class RaporController extends Controller
{
    /**
     * Display a listing of rapor.
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
        $kelas = $user->kelasAsWali;
        
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

        $query = Rapor::whereIn('siswa_id', $siswaIds)
                     ->with(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran', 'approver']);

        if ($tahunAjaranId) {
            $query->where('tahun_ajaran_id', $tahunAjaranId);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('siswa_id')) {
            $query->where('siswa_id', $request->siswa_id);
        }

        $rapor = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($rapor);
    }

    /**
     * Generate rapor for a student.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
        ]);

        $user = Auth::user();
        $siswa = Siswa::find($request->siswa_id);

        // Verify that user is wali kelas for this student's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda bukan wali kelas untuk siswa ini',
            ], 403);
        }

        // Check if rapor already exists
        $existing = Rapor::where('siswa_id', $request->siswa_id)
                         ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
                         ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Rapor untuk siswa ini pada tahun ajaran ini sudah ada',
                'data' => $existing->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']),
            ], 422);
        }

        // Check if all required data is complete
        $nilai = $siswa->nilai()->where('tahun_ajaran_id', $request->tahun_ajaran_id)->count();
        $kehadiran = $siswa->kehadiran()->where('tahun_ajaran_id', $request->tahun_ajaran_id)->exists();
        $catatan = $siswa->catatanAkademik()->where('tahun_ajaran_id', $request->tahun_ajaran_id)->exists();

        if ($nilai === 0 || !$kehadiran || !$catatan) {
            return response()->json([
                'message' => 'Data belum lengkap. Pastikan nilai, kehadiran, dan catatan akademik sudah diinput',
                'data' => [
                    'nilai_count' => $nilai,
                    'has_kehadiran' => $kehadiran,
                    'has_catatan' => $catatan,
                ],
            ], 422);
        }

        $rapor = Rapor::create([
            'siswa_id' => $request->siswa_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'kelas_id' => $siswa->kelas_id,
            'tanggal_rapor' => now(),
            'status' => 'draft',
        ]);

        return response()->json([
            'message' => 'Rapor berhasil dibuat',
            'data' => $rapor->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']),
        ], 201);
    }

    /**
     * Display the specified rapor.
     *
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Rapor $rapor)
    {
        $user = Auth::user();

        // Verify that this rapor belongs to a student in wali kelas's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $rapor->siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat rapor ini',
            ], 403);
        }

        $rapor->load([
            'siswa.user',
            'siswa.kelas.jurusan',
            'tahunAjaran',
            'approver',
        ]);

        // Load related data
        $rapor->nilai = $rapor->nilai;
        $rapor->kehadiran = $rapor->kehadiran;
        $rapor->catatan_akademik = $rapor->catatan_akademik;

        return response()->json($rapor);
    }

    /**
     * Submit rapor for approval.
     *
     * @param  Request  $request
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request, Rapor $rapor)
    {
        $user = Auth::user();

        // Verify that this rapor belongs to a student in wali kelas's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $rapor->siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengirim rapor ini',
            ], 403);
        }

        if ($rapor->status !== 'draft') {
            return response()->json([
                'message' => 'Rapor sudah dikirim atau disetujui',
            ], 422);
        }

        // Verify that rapor is complete
        if (!$rapor->isComplete()) {
            return response()->json([
                'message' => 'Rapor belum lengkap. Pastikan semua data sudah diinput',
            ], 422);
        }

        $rapor->update([
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Rapor berhasil dikirim untuk persetujuan',
            'data' => $rapor->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran', 'approver']),
        ]);
    }

    /**
     * Preview rapor (before submission).
     *
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Rapor $rapor)
    {
        $user = Auth::user();

        // Verify that this rapor belongs to a student in wali kelas's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $rapor->siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat rapor ini',
            ], 403);
        }

        $rapor->load([
            'siswa.user',
            'siswa.kelas.jurusan',
            'tahunAjaran',
        ]);

        // Load all related data for preview
        $rapor->nilai = $rapor->nilai;
        $rapor->kehadiran = $rapor->kehadiran;
        $rapor->catatan_akademik = $rapor->catatan_akademik;
        $rapor->nilai_ekstrakurikuler = $rapor->siswa->nilaiEkstrakurikuler()
                                                      ->where('tahun_ajaran_id', $rapor->tahun_ajaran_id)
                                                      ->with('ekstrakurikuler')
                                                      ->get();
        $rapor->nilai_p5 = $rapor->siswa->nilaiP5()
                                        ->with(['p5', 'dimensi'])
                                        ->get();

        return response()->json($rapor);
    }

    /**
     * Download rapor as PDF.
     *
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\Response
     */
    public function download(Rapor $rapor)
    {
        $user = Auth::user();

        // Verify that this rapor belongs to a student in wali kelas's class
        $isWaliKelas = $user->kelasAsWali->contains('id', $rapor->siswa->kelas_id);
        if (!$isWaliKelas) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengunduh rapor ini',
            ], 403);
        }

        // TODO: Implement PDF generation using DomPDF
        // For now, return JSON response
        $rapor->load([
            'siswa.user',
            'siswa.kelas.jurusan',
            'tahunAjaran',
            'approver',
        ]);

        return response()->json([
            'message' => 'PDF generation akan diimplementasikan',
            'data' => $rapor,
        ]);
    }
}

