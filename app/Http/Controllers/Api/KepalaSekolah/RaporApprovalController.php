<?php

namespace App\Http\Controllers\Api\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\Rapor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * RaporApprovalController for Kepala Sekolah
 * 
 * Handles rapor approval for principal
 */
class RaporApprovalController extends Controller
{
    /**
     * Get pending rapor for approval.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pending(Request $request)
    {
        $query = Rapor::where('status', 'pending')
                     ->with(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']);

        if ($request->has('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        if ($request->has('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
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
     * Approve a rapor.
     *
     * @param  Request  $request
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, Rapor $rapor)
    {
        if ($rapor->status !== 'pending') {
            return response()->json([
                'message' => 'Rapor tidak dalam status pending',
            ], 422);
        }

        $user = Auth::user();

        $rapor->approve($user->id);

        return response()->json([
            'message' => 'Rapor berhasil disetujui',
            'data' => $rapor->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran', 'approver']),
        ]);
    }

    /**
     * Reject a rapor.
     *
     * @param  Request  $request
     * @param  Rapor  $rapor
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, Rapor $rapor)
    {
        if ($rapor->status !== 'pending') {
            return response()->json([
                'message' => 'Rapor tidak dalam status pending',
            ], 422);
        }

        $request->validate([
            'alasan' => 'nullable|string|max:500',
        ]);

        $rapor->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'message' => 'Rapor ditolak dan dikembalikan ke draft',
            'data' => $rapor->load(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran']),
        ]);
    }

    /**
     * Batch approve multiple rapor.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchApprove(Request $request)
    {
        $request->validate([
            'rapor_ids' => 'required|array',
            'rapor_ids.*' => 'exists:rapor,id',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $rapor = Rapor::whereIn('id', $request->rapor_ids)
                         ->where('status', 'pending')
                         ->get();

            foreach ($rapor as $r) {
                $r->approve($user->id);
            }

            DB::commit();

            return response()->json([
                'message' => count($rapor) . ' rapor berhasil disetujui',
                'approved_count' => count($rapor),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyetujui rapor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

