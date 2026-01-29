<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaliKelas;
use App\Models\Guru;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * WaliKelasController
 * 
 * Handles CRUD operations for wali kelas assignments (admin only)
 */
class WaliKelasController extends Controller
{
    /**
     * Display a listing of wali kelas.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = WaliKelas::with(['guru.user', 'kelas.jurusan', 'kelas.siswa'])
                          ->where('is_active', true);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('guru', function ($qu) use ($search) {
                    $qu->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nuptk', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($qus) use ($search) {
                          $qus->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                })
                ->orWhereHas('kelas', function ($qu) use ($search) {
                    $qu->where('nama_kelas', 'like', "%{$search}%")
                      ->orWhereHas('jurusan', function ($qj) use ($search) {
                          $qj->where('nama_jurusan', 'like', "%{$search}%");
                      });
                });
            });
        }

        if ($request->has('kelas_id') && $request->kelas_id) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->has('guru_id') && $request->guru_id) {
            $query->where('guru_id', $request->guru_id);
        }

        if ($request->has('jurusan_id') && $request->jurusan_id) {
            $query->whereHas('kelas', function ($q) use ($request) {
                $q->where('jurusan_id', $request->jurusan_id);
            });
        }

        $waliKelas = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        // Transform data to group by guru
        $grouped = $waliKelas->getCollection()->groupBy('guru_id')->map(function ($items, $guruId) {
            $first = $items->first();
            $guru = $first->guru;
            $user = $guru->user;
            
            return [
                'id' => $user->id,
                'guru_id' => $guru->id,
                'name' => $user->name,
                'email' => $user->email,
                'guru' => $guru,
                'kelas_as_wali' => $items->map(function ($item) {
                    $kelas = $item->kelas;
                    // Add wali_kelas_id to kelas data for easy reference
                    $kelas->wali_kelas_id = $item->id;
                    return $kelas;
                }),
                'total_kelas' => $items->count(),
                'total_siswa' => $items->sum(function ($item) {
                    return $item->kelas->siswa()->where('status', 'aktif')->count();
                }),
            ];
        })->values();

        return response()->json([
            'data' => $grouped,
            'current_page' => $waliKelas->currentPage(),
            'last_page' => $waliKelas->lastPage(),
            'per_page' => $waliKelas->perPage(),
            'total' => $grouped->count(),
        ]);
    }

    /**
     * Store a newly created wali kelas assignment.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => ['required', 'exists:guru,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after:tanggal_mulai'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $guru = Guru::find($request->guru_id);
        $kelas = Kelas::find($request->kelas_id);

        if (!$guru->user || $guru->user->role !== 'guru') {
            return response()->json([
                'message' => 'Guru yang dipilih harus memiliki role guru',
            ], 422);
        }

        // Check if kelas already has active wali kelas
        $existingWaliKelas = WaliKelas::where('kelas_id', $request->kelas_id)
                                     ->where('is_active', true)
                                     ->first();

        if ($existingWaliKelas) {
            return response()->json([
                'message' => 'Kelas sudah memiliki wali kelas aktif. Nonaktifkan wali kelas yang ada terlebih dahulu.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $waliKelas = WaliKelas::create([
                'guru_id' => $request->guru_id,
                'kelas_id' => $request->kelas_id,
                'tanggal_mulai' => $request->tanggal_mulai ?? now(),
                'tanggal_selesai' => $request->tanggal_selesai,
                'is_active' => true,
                'keterangan' => $request->keterangan,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Wali kelas berhasil ditetapkan',
                'data' => $waliKelas->load(['guru.user', 'kelas.jurusan']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menetapkan wali kelas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified wali kelas.
     *
     * @param  WaliKelas  $waliKelas
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(WaliKelas $waliKelas)
    {
        $waliKelas->load([
            'guru.user',
            'kelas.jurusan',
            'kelas.siswa.user',
        ]);

        return response()->json($waliKelas);
    }

    /**
     * Update wali kelas assignment.
     *
     * @param  Request  $request
     * @param  WaliKelas  $waliKelas
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, WaliKelas $waliKelas)
    {
        $request->validate([
            'guru_id' => ['sometimes', 'exists:guru,id'],
            'kelas_id' => ['sometimes', 'exists:kelas,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after:tanggal_mulai'],
            'is_active' => ['sometimes', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            // If changing kelas_id, use simple logic: remove from old class, assign to new class
            if ($request->has('kelas_id')) {
                $newKelasId = (int) $request->kelas_id;
                
                // Get current data - refresh model to ensure we have latest data
                $waliKelas->refresh();
                $currentKelasId = (int) $waliKelas->kelas_id;
                $guruId = (int) $waliKelas->guru_id;
                
                if (!$guruId || $guruId <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Guru ID tidak ditemukan pada penetapan wali kelas ini.',
                    ], 422);
                }
                
                if ($newKelasId != $currentKelasId) {
                    // Step 1: Nonaktifkan/hapus penetapan dari kelas lama
                    $waliKelas->update(['is_active' => false]);
                    
                    // Step 2: Check if new kelas already has active wali kelas
                    $existingWaliKelas = WaliKelas::where('kelas_id', $newKelasId)
                                                 ->where('is_active', true)
                                                 ->first();

                    if ($existingWaliKelas) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Kelas sudah memiliki wali kelas aktif.',
                        ], 422);
                    }
                    
                    // Step 3: Check if there's an inactive assignment for this guru and new kelas
                    $inactiveWaliKelas = WaliKelas::where('guru_id', $guruId)
                                                  ->where('kelas_id', $newKelasId)
                                                  ->where('is_active', false)
                                                  ->first();
                    
                    if ($inactiveWaliKelas) {
                        // Reactivate existing assignment
                        $inactiveWaliKelas->update([
                            'is_active' => true,
                            'tanggal_mulai' => $request->tanggal_mulai ?? now(),
                            'tanggal_selesai' => $request->tanggal_selesai ?? $inactiveWaliKelas->tanggal_selesai,
                            'keterangan' => $request->keterangan ?? $inactiveWaliKelas->keterangan,
                        ]);
                        
                        $waliKelas = $inactiveWaliKelas;
                    } else {
                        // Create new assignment - ensure guru_id is not null
                        if (!$guruId || $guruId <= 0) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Guru ID tidak valid untuk membuat penetapan baru.',
                                'debug' => ['guru_id' => $guruId, 'current_wali_kelas_id' => $waliKelas->id],
                            ], 422);
                        }
                        
                        // Ensure all required fields are set
                        $createData = [
                            'guru_id' => $guruId,
                            'kelas_id' => $newKelasId,
                            'tanggal_mulai' => $request->tanggal_mulai ?? now(),
                            'is_active' => true,
                        ];
                        
                        if ($request->has('tanggal_selesai')) {
                            $createData['tanggal_selesai'] = $request->tanggal_selesai;
                        }
                        
                        if ($request->has('keterangan')) {
                            $createData['keterangan'] = $request->keterangan;
                        }
                        
                        $waliKelas = WaliKelas::create($createData);
                    }
                } else {
                    // Same kelas, just update other fields
                    $updateData = $request->only([
                        'tanggal_mulai',
                        'tanggal_selesai',
                        'is_active',
                        'keterangan',
                    ]);
                    
                    $waliKelas->update($updateData);
                }
            } else {
                // No kelas_id change, just update other fields
                $updateData = $request->only([
                    'guru_id',
                    'tanggal_mulai',
                    'tanggal_selesai',
                    'is_active',
                    'keterangan',
                ]);
                
                if (isset($updateData['guru_id'])) {
                    $updateData['guru_id'] = (int) $updateData['guru_id'];
                }
                
                $waliKelas->update($updateData);
            }

            DB::commit();

            // Refresh the model and load relationships
            $waliKelas = $waliKelas->fresh(['guru.user', 'kelas.jurusan']);

            return response()->json([
                'message' => 'Wali kelas berhasil diperbarui',
                'data' => $waliKelas,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating wali kelas', [
                'id' => $waliKelas->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Gagal memperbarui wali kelas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove wali kelas assignment.
     *
     * @param  WaliKelas  $waliKelas
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(WaliKelas $waliKelas)
    {
        DB::beginTransaction();
        try {
            // Permanently delete the record from database
            $waliKelas->delete();

            DB::commit();

            return response()->json([
                'message' => 'Wali kelas berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus wali kelas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get kelas for a specific guru (wali kelas).
     *
     * @param  Guru  $guru
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKelas(Guru $guru)
    {
        $waliKelas = $guru->waliKelasAktif()
                          ->with(['kelas.jurusan', 'kelas.siswa.user'])
                          ->get();

        $kelas = $waliKelas->map(function ($wk) {
            $k = $wk->kelas;
            $k->active_siswa_count = $k->activeSiswa()->count();
            $k->is_full = $k->is_full;
            $k->available_capacity = $k->available_capacity;
            return $k;
        });

        return response()->json($kelas);
    }

    /**
     * Assign wali kelas to kelas (alternative endpoint).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(Request $request)
    {
        $request->validate([
            'guru_id' => ['required', 'exists:guru,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after:tanggal_mulai'],
            'keterangan' => ['nullable', 'string'],
        ]);

        return $this->store($request);
    }

    /**
     * Remove wali kelas from kelas (alternative endpoint).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request)
    {
        $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);

        $waliKelas = WaliKelas::where('kelas_id', $request->kelas_id)
                              ->where('is_active', true)
                              ->first();

        if (!$waliKelas) {
            return response()->json([
                'message' => 'Kelas tidak memiliki wali kelas aktif',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Permanently delete the record from database
            $waliKelas->delete();

            DB::commit();

            return response()->json([
                'message' => 'Wali kelas berhasil dihapus dari kelas',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus wali kelas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get WaliKelas ID by guru_id and kelas_id.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findId(Request $request)
    {
        $request->validate([
            'guru_id' => ['required', 'exists:guru,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);

        // Find active wali kelas first, then try inactive if not found
        $waliKelas = WaliKelas::where('guru_id', $request->guru_id)
                              ->where('kelas_id', $request->kelas_id)
                              ->where('is_active', true)
                              ->first();
        
        // If not found, try without is_active filter (for editing purposes)
        if (!$waliKelas) {
            $waliKelas = WaliKelas::where('guru_id', $request->guru_id)
                                  ->where('kelas_id', $request->kelas_id)
                                  ->orderBy('is_active', 'desc')
                                  ->orderBy('created_at', 'desc')
                                  ->first();
        }
        
        if (!$waliKelas) {
            return response()->json([
                'message' => 'Wali kelas tidak ditemukan untuk kombinasi guru dan kelas ini',
                'guru_id' => $request->guru_id,
                'kelas_id' => $request->kelas_id,
            ], 404);
        }

        return response()->json([
            'id' => $waliKelas->id,
            'data' => $waliKelas->load(['guru.user', 'kelas.jurusan']),
        ]);
    }
}
