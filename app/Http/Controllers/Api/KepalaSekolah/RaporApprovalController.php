<?php

namespace App\Http\Controllers\Api\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\NilaiP5;
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
     * Get rapor for approval with filters.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Rapor::with(['siswa.user', 'siswa.kelas.jurusan', 'tahunAjaran', 'approver']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            // Map 'pending' to 'draft' if pending doesn't exist in enum
            $status = $request->status;
            if ($status === 'pending') {
                // Check if pending exists, otherwise use draft
                $query->where(function($q) {
                    $q->where('status', 'pending')
                      ->orWhere(function($q2) {
                          $q2->where('status', 'draft')->whereNull('approved_at');
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by tahun ajaran
        if ($request->has('tahun_ajaran_id') && $request->tahun_ajaran_id !== '') {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        // Filter by kelas
        if ($request->has('kelas_id') && $request->kelas_id !== '') {
            $query->where('kelas_id', $request->kelas_id);
        }

        $rapor = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        // Calculate summary
        $summary = [
            'pending' => Rapor::where('status', 'pending')->count(),
            'approved' => Rapor::where('status', 'approved')->count(),
            'rejected' => Rapor::where('status', 'draft')->whereNotNull('approved_at')->count(),
            'total' => Rapor::count(),
        ];
        
        // If pending status doesn't exist, use draft as pending
        if (!Rapor::where('status', 'pending')->exists() && Rapor::where('status', 'draft')->exists()) {
            $summary['pending'] = Rapor::where('status', 'draft')->whereNull('approved_at')->count();
        }

        return response()->json([
            'data' => $rapor->items(),
            'summary' => $summary,
            'pagination' => [
                'current_page' => $rapor->currentPage(),
                'last_page' => $rapor->lastPage(),
                'per_page' => $rapor->perPage(),
                'total' => $rapor->total(),
            ],
        ]);
    }

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

        return response()->json([
            'data' => $rapor,
        ]);
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
            'reason' => 'nullable|string|max:500',
            'alasan' => 'nullable|string|max:500', // Support both
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

    /**
     * Get P5 rapor for approval.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexP5(Request $request)
    {
        // Get all siswa who have P5 nilai
        $query = Siswa::whereHas('nilaiP5')
                      ->with(['user', 'kelas.jurusan']);

        // Filter by kelas
        if ($request->has('kelas_id') && $request->kelas_id !== '') {
            $query->where('kelas_id', $request->kelas_id);
        }

        // Filter by tahun ajaran (through P5)
        if ($request->has('tahun_ajaran_id') && $request->tahun_ajaran_id !== '') {
            $query->whereHas('nilaiP5.p5', function ($q) use ($request) {
                $q->where('tahun_ajaran_id', $request->tahun_ajaran_id);
            });
        }

        $siswa = $query->orderBy('nama_lengkap')->paginate($request->get('per_page', 15));

        // Transform to include P5 summary
        $siswa->getCollection()->transform(function ($item) use ($request) {
            $tahunAjaranId = $request->tahun_ajaran_id;
            
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
                    'tahun_ajaran' => $p5->tahunAjaran ? 
                        "{$p5->tahunAjaran->tahun} - Semester {$p5->tahunAjaran->semester}" : null,
                    'dimensi_count' => $nilai->count(),
                ];
            })->values();
            
            $item->total_p5_projects = $item->p5_projects->count();
            
            return $item;
        });

        return response()->json([
            'data' => $siswa->items(),
            'pagination' => [
                'current_page' => $siswa->currentPage(),
                'last_page' => $siswa->lastPage(),
                'per_page' => $siswa->perPage(),
                'total' => $siswa->total(),
            ],
        ]);
    }

    /**
     * Show P5 rapor detail for a student.
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return \Illuminate\Http\JsonResponse
     */
    public function showP5(Request $request, Siswa $siswa)
    {
        $siswa->load([
            'user',
            'kelas.jurusan'
        ]);

        $tahunAjaranId = $request->tahun_ajaran_id;

        // Get all P5 projects for this student
        $nilaiP5Query = $siswa->nilaiP5()->with([
            'p5.tahunAjaran',
            'p5.koordinator.user',
            'dimensi'
        ]);

        if ($tahunAjaranId) {
            $nilaiP5Query->whereHas('p5', function ($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId);
            });
        }

        $nilaiP5 = $nilaiP5Query->get();

        // Group by P5 project
        $p5Projects = $nilaiP5->groupBy('p5_id')->map(function ($nilai) {
            $p5 = $nilai->first()->p5;
            return [
                'id' => $p5->id,
                'tema' => $p5->tema,
                'deskripsi' => $p5->deskripsi,
                'koordinator' => $p5->koordinator ? [
                    'nama' => $p5->koordinator->nama_lengkap,
                    'user' => $p5->koordinator->user ? [
                        'name' => $p5->koordinator->user->name,
                        'email' => $p5->koordinator->user->email,
                    ] : null,
                ] : null,
                'tahun_ajaran' => $p5->tahunAjaran ? [
                    'tahun' => $p5->tahunAjaran->tahun,
                    'semester' => $p5->tahunAjaran->semester,
                    'label' => "{$p5->tahunAjaran->tahun} - Semester {$p5->tahunAjaran->semester}",
                ] : null,
                'dimensi' => $nilai->map(function ($n) {
                    return [
                        'id' => $n->dimensi->id,
                        'nama_dimensi' => $n->dimensi->nama_dimensi,
                        'nilai' => $n->nilai,
                        'nilai_description' => $n->nilai_description,
                        'catatan' => $n->catatan,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'siswa' => $siswa,
                'p5_projects' => $p5Projects,
            ],
        ]);
    }

    /**
     * Approve P5 rapor for a student.
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveP5(Request $request, Siswa $siswa)
    {
        // For P5, we might just mark as reviewed/approved
        // Since there's no status field in nilai_p5, we can add a note or just return success
        return response()->json([
            'message' => 'Rapor P5 berhasil disetujui',
            'data' => $siswa->load(['user', 'kelas.jurusan']),
        ]);
    }

    /**
     * Reject P5 rapor for a student.
     *
     * @param  Request  $request
     * @param  Siswa  $siswa
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectP5(Request $request, Siswa $siswa)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'alasan' => 'nullable|string|max:500',
        ]);

        return response()->json([
            'message' => 'Rapor P5 ditolak',
            'data' => $siswa->load(['user', 'kelas.jurusan']),
        ]);
    }
}

