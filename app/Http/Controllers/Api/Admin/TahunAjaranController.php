<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * TahunAjaranController
 * 
 * Handles CRUD operations for tahun ajaran (admin only)
 */
class TahunAjaranController extends Controller
{
    /**
     * Display a listing of tahun ajaran.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = TahunAjaran::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tahun', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
        }

        $tahunAjaran = $query->orderBy('tahun', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($tahunAjaran);
    }

    /**
     * Store a newly created tahun ajaran.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Check if this is the new format (with active_semester only)
        if ($request->has('active_semester')) {
            $validated = $request->validate([
                'tahun' => ['required', 'string', 'max:255'],
                'active_semester' => ['required', Rule::in(['1', '2'])],
            ]);

            DB::beginTransaction();
            try {
                $activeSemester = $validated['active_semester'];
                $tahun = $validated['tahun'];

                // Deactivate all others first
                TahunAjaran::where('is_active', true)->update(['is_active' => false]);

                // Existing records for this tahun (keyed by semester)
                $existing = TahunAjaran::where('tahun', $tahun)->get()->keyBy('semester');
                $created = [];

                // Create semester 1 only if it doesn't exist
                if (! $existing->has('1')) {
                    $tahunAjaran1 = TahunAjaran::create([
                        'tahun' => $tahun,
                        'semester' => '1',
                        'is_active' => ($activeSemester === '1'),
                    ]);
                    $created[] = $tahunAjaran1;
                } else {
                    $existing->get('1')->update(['is_active' => $activeSemester === '1']);
                }

                // Create semester 2 only if it doesn't exist
                if (! $existing->has('2')) {
                    $tahunAjaran2 = TahunAjaran::create([
                        'tahun' => $tahun,
                        'semester' => '2',
                        'is_active' => ($activeSemester === '2'),
                    ]);
                    $created[] = $tahunAjaran2;
                } else {
                    $existing->get('2')->update(['is_active' => $activeSemester === '2']);
                }

                DB::commit();

                return response()->json([
                    'message' => 'Tahun ajaran berhasil ditambahkan',
                    'data' => $created,
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal menambahkan tahun ajaran',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            // Old format (backward compatibility)
            $validated = $request->validate([
                'tahun' => ['required', 'string', 'max:255'],
                'semester' => ['required', Rule::in(['1', '2'])],
                'is_active' => ['nullable', 'boolean'],
            ]);

            DB::beginTransaction();
            try {
                // If setting as active, deactivate all others
                if ($request->has('is_active') && $request->is_active) {
                    TahunAjaran::where('is_active', true)->update(['is_active' => false]);
                }

                $tahunAjaran = TahunAjaran::create([
                    'tahun' => $validated['tahun'],
                    'semester' => $validated['semester'],
                    'is_active' => $validated['is_active'] ?? false,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Tahun ajaran berhasil ditambahkan',
                    'data' => $tahunAjaran,
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal menambahkan tahun ajaran',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
    }

    /**
     * Display the specified tahun ajaran.
     *
     * @param  \App\Models\TahunAjaran  $tahunAjaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(TahunAjaran $tahunAjaran)
    {
        return response()->json($tahunAjaran);
    }

    /**
     * Update the specified tahun ajaran.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TahunAjaran  $tahunAjaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        // Check if this is a request to change active semester
        if ($request->has('active_semester')) {
            $validated = $request->validate([
                'active_semester' => ['required', Rule::in(['1', '2'])],
            ]);

            DB::beginTransaction();
            try {
                $newActiveSemester = $validated['active_semester'];
                $currentTahun = $tahunAjaran->tahun;
                
                // Deactivate all tahun ajaran first
                TahunAjaran::where('is_active', true)->update(['is_active' => false]);
                
                // Find the record with same tahun but the selected semester
                $targetTahunAjaran = TahunAjaran::where('tahun', $currentTahun)
                    ->where('semester', $newActiveSemester)
                    ->first();
                
                if ($targetTahunAjaran) {
                    // Activate the target record (the one with selected semester)
                    $targetTahunAjaran->update(['is_active' => true]);
                    
                    // Ensure current record is inactive (if it's not the target)
                    if ($tahunAjaran->id != $targetTahunAjaran->id) {
                        $tahunAjaran->update(['is_active' => false]);
                    }
                    
                    // Return the activated record
                    $targetTahunAjaran->refresh();
                    
                    DB::commit();
                    
                    return response()->json([
                        'message' => 'Tahun ajaran berhasil diperbarui',
                        'data' => $targetTahunAjaran,
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Tahun ajaran dengan semester yang dipilih tidak ditemukan',
                    ], 404);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal memperbarui tahun ajaran',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            // Normal update
            $validated = $request->validate([
                'tahun' => ['sometimes', 'string', 'max:255'],
                'semester' => ['sometimes', Rule::in(['1', '2'])],
                'is_active' => ['nullable', 'boolean'],
            ]);

            DB::beginTransaction();
            try {
                // If setting as active, deactivate all others first
                if ($request->has('is_active') && $request->is_active) {
                    TahunAjaran::where('is_active', true)->update(['is_active' => false]);
                }
                
                $tahunAjaran->update($validated);

                DB::commit();

                // Reload the updated record
                $tahunAjaran->refresh();

                return response()->json([
                    'message' => 'Tahun ajaran berhasil diperbarui',
                    'data' => $tahunAjaran,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal memperbarui tahun ajaran',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
    }

    /**
     * Remove the specified tahun ajaran.
     *
     * @param  \App\Models\TahunAjaran  $tahunAjaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TahunAjaran $tahunAjaran)
    {
        DB::beginTransaction();
        try {
            // Check if tahun ajaran is active
            if ($tahunAjaran->is_active) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus tahun ajaran yang sedang aktif',
                ], 422);
            }

            // Check if tahun ajaran has related data
            $hasRelatedData = $tahunAjaran->nilai()->exists() ||
                             $tahunAjaran->nilaiEkstrakurikuler()->exists() ||
                             $tahunAjaran->p5()->exists() ||
                             $tahunAjaran->kehadiran()->exists() ||
                             $tahunAjaran->ukk()->exists() ||
                             $tahunAjaran->catatanAkademik()->exists() ||
                             $tahunAjaran->rapor()->exists();

            if ($hasRelatedData) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus tahun ajaran yang sudah memiliki data terkait',
                ], 422);
            }

            $tahunAjaran->delete();

            DB::commit();

            return response()->json([
                'message' => 'Tahun ajaran berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus tahun ajaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate the specified tahun ajaran.
     *
     * @param  \App\Models\TahunAjaran  $tahunAjaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(TahunAjaran $tahunAjaran)
    {
        DB::beginTransaction();
        try {
            // Deactivate all other tahun ajaran
            TahunAjaran::where('is_active', true)->update(['is_active' => false]);

            // Activate this tahun ajaran
            $tahunAjaran->update(['is_active' => true]);

            DB::commit();

            return response()->json([
                'message' => 'Tahun ajaran berhasil diaktifkan',
                'data' => $tahunAjaran,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengaktifkan tahun ajaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

