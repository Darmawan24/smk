<?php

namespace App\Http\Controllers\Api\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Rapor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RaporSiswaController for Siswa
 * 
 * Handles rapor viewing for students
 */
class RaporSiswaController extends Controller
{
    /**
     * Display a listing of rapor for the authenticated student.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404);
        }

        $query = Rapor::where('siswa_id', $siswa->id)
                     ->with(['tahunAjaran', 'kelas.jurusan', 'approver']);

        if ($request->has('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $rapor = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($rapor);
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
        $siswa = $user->siswa;

        // Verify that this rapor belongs to the authenticated student
        if ($rapor->siswa_id !== $siswa->id) {
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
        $siswa = $user->siswa;

        // Verify that this rapor belongs to the authenticated student
        if ($rapor->siswa_id !== $siswa->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengunduh rapor ini',
            ], 403);
        }

        // Only allow download if rapor is approved or published
        if (!in_array($rapor->status, ['approved', 'published'])) {
            return response()->json([
                'message' => 'Rapor belum disetujui',
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

