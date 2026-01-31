<?php

namespace App\Http\Controllers\Api\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\P5;
use App\Models\NilaiP5;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CekPenilaianController for Wali Kelas
 * 
 * Handles penilaian checking for homeroom teachers
 */
class CekPenilaianController extends Controller
{
    /**
     * Get mata pelajaran for STS (Sumatif Tengah Semester) checking.
     * Returns all mata pelajaran from classes managed by the wali-kelas.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sts(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isWaliKelas()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get active classes where user is wali kelas (sumber yang sama untuk filter kelas di STS/SAS/P5)
        $kelas = $user->kelasAsWali();
        // jurusan sudah di-load dari User::kelasAsWali() via with('kelas.jurusan')

        $kelasList = $kelas->map(function ($k) {
            return [
                'id' => $k->id,
                'nama_kelas' => $k->nama_kelas,
                'tingkat' => $k->tingkat,
                'jurusan_id' => $k->jurusan_id,
                'jurusan' => $k->jurusan ? [
                    'id' => $k->jurusan->id,
                    'nama_jurusan' => $k->jurusan->nama_jurusan,
                ] : null,
            ];
        })->values()->all();

        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
                'kelas' => [],
                'mata_pelajaran' => []
            ], 200);
        }

        // Filter by kelas_id if provided
        $kelasIds = $kelas->pluck('id');
        if ($request->has('kelas_id') && $request->kelas_id) {
            $kelasIds = $kelasIds->intersect([$request->kelas_id]);
            if ($kelasIds->isEmpty()) {
                return response()->json([
                    'message' => 'Kelas tidak ditemukan atau tidak diwalikan oleh Anda',
                    'kelas' => $kelasList,
                    'mata_pelajaran' => [],
                ], 200);
            }
        }

        // Get tahun ajaran aktif
        $tahunAjaran = \App\Models\TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
                'kelas' => $kelasList,
                'mata_pelajaran' => [],
            ], 200);
        }

        // Get all mata pelajaran from filtered classes
        $mataPelajaranIds = collect();
        foreach ($kelas->whereIn('id', $kelasIds) as $k) {
            $mapelIds = $k->mataPelajaran()
                ->where('mata_pelajaran.is_active', true)
                ->pluck('mata_pelajaran.id');
            $mataPelajaranIds = $mataPelajaranIds->merge($mapelIds);
        }

        // Get unique mata pelajaran
        $uniqueMapelIds = $mataPelajaranIds->unique()->values();
        
        if ($uniqueMapelIds->isEmpty()) {
            return response()->json([
                'kelas' => $kelasList,
                'mata_pelajaran' => [],
            ]);
        }

        $mataPelajaran = MataPelajaran::whereIn('id', $uniqueMapelIds)
            ->where('is_active', true)
            ->with(['guru.user'])
            ->orderBy('nama_mapel')
            ->get();

        // Get semester from request (default to 1 for STS)
        $semester = $request->has('semester') && $request->semester ? $request->semester : '1';

        // Get tingkat from selected classes
        $tingkatList = \App\Models\Kelas::whereIn('id', $kelasIds)
            ->distinct('tingkat')
            ->pluck('tingkat')
            ->filter()
            ->values();

        // Calculate progress for each mata pelajaran
        $mataPelajaran->each(function($mapel) use ($kelasIds, $tahunAjaran, $semester, $tingkatList) {
            // Get all active students from filtered classes
            $totalSiswa = \App\Models\Siswa::whereIn('kelas_id', $kelasIds)
                ->where('status', 'aktif')
                ->count();

            // Get all active CP for this mata pelajaran that match semester, tingkat, and target (tengah_semester for STS)
            $allCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mapel->id)
                ->where('is_active', true)
                ->where(function($query) use ($semester, $tingkatList) {
                    // Filter by semester: CP must have semester matching selected semester, or no semester (backward compatibility)
                    $query->where(function($q) use ($semester) {
                        $q->where('semester', $semester)
                          ->orWhereNull('semester');
                    });
                    
                    // Filter by tingkat: CP must have tingkat matching selected kelas tingkat, or no tingkat (backward compatibility)
                    if ($tingkatList->isNotEmpty()) {
                        $query->where(function($q) use ($tingkatList) {
                            $q->whereIn('tingkat', $tingkatList)
                              ->orWhereNull('tingkat');
                        });
                    }
                })
                ->where('target', 'tengah_semester') // Only CP with target 'tengah_semester' for STS
                ->get();

            // Get STS CP: dari $allCP dulu, kalau tidak ada (mis. STS beda tingkat) cari STS mapel ini tanpa filter tingkat
            $stsCP = $allCP->where('kode_cp', 'STS')->first();
            if (!$stsCP) {
                $stsCP = $allCP->where('target', 'tengah_semester')->where('kode_cp', 'like', 'CP-%')->first();
            }
            if (!$stsCP) {
                $stsCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mapel->id)
                    ->where('kode_cp', 'STS')
                    ->where('is_active', true)
                    ->where(function ($q) use ($semester) {
                        $q->where('semester', $semester)->orWhereNull('semester');
                    })
                    ->first();
            }

            // Get other CP (exclude STS/SAS kode_cp, but include all CP with tengah_semester target)
            $otherCP = $allCP->where('kode_cp', '!=', 'STS')
                            ->where('kode_cp', '!=', 'SAS')
                            ->where('target', 'tengah_semester');
            
            $progressData = [];
            $hasActiveCP = $otherCP->isNotEmpty();

            // Calculate progress for all active CP that have nilai in the selected semester (exclude STS and SAS)
            foreach ($otherCP as $cp) {
                $siswaSudahInput = Nilai::where('mata_pelajaran_id', $mapel->id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->where('semester', $semester)
                    ->where('capaian_pembelajaran_id', $cp->id)
                    ->whereHas('siswa', function($q) use ($kelasIds) {
                        $q->whereIn('kelas_id', $kelasIds)->where('status', 'aktif');
                    })
                    ->distinct('siswa_id')
                    ->count('siswa_id');

                $progressData[] = [
                    'capaian_pembelajaran_id' => $cp->id,
                    'kode_cp' => $cp->kode_cp,
                    'sudah_input' => $siswaSudahInput,
                    'total' => $totalSiswa,
                    'lengkap' => $siswaSudahInput == $totalSiswa && $totalSiswa > 0,
                ];
            }

            // Always show STS if there are active CP (other than STS/SAS) that have nilai in the selected semester
            if ($stsCP || $hasActiveCP) {
                $stsSudahInput = 0;
                if ($stsCP) {
                    $stsSudahInput = Nilai::where('mata_pelajaran_id', $mapel->id)
                        ->where('tahun_ajaran_id', $tahunAjaran->id)
                        ->where('semester', $semester)
                        ->where('capaian_pembelajaran_id', $stsCP->id)
                        ->whereHas('siswa', function($q) use ($kelasIds) {
                            $q->whereIn('kelas_id', $kelasIds)->where('status', 'aktif');
                        })
                        ->distinct('siswa_id')
                        ->count('siswa_id');
                }

                $progressData[] = [
                    'capaian_pembelajaran_id' => $stsCP ? $stsCP->id : null,
                    'kode_cp' => 'STS',
                    'sudah_input' => $stsSudahInput,
                    'total' => $totalSiswa,
                    'lengkap' => $stsSudahInput == $totalSiswa && $totalSiswa > 0,
                ];
            }

            $mapel->progress = [
                'total_siswa' => $totalSiswa,
                'total_cp' => count($progressData),
                'progress_data' => $progressData,
            ];
        });

        return response()->json([
            'kelas' => $kelasList,
            'mata_pelajaran' => $mataPelajaran,
        ]);
    }

    /**
     * Get mata pelajaran for SAS (Sumatif Akhir Semester) checking.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sas(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isWaliKelas()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get active classes where user is wali kelas (sumber yang sama untuk filter kelas di STS/SAS/P5)
        $kelas = $user->kelasAsWali();
        // jurusan sudah di-load dari User::kelasAsWali() via with('kelas.jurusan')

        $kelasList = $kelas->map(function ($k) {
            return [
                'id' => $k->id,
                'nama_kelas' => $k->nama_kelas,
                'tingkat' => $k->tingkat,
                'jurusan_id' => $k->jurusan_id,
                'jurusan' => $k->jurusan ? [
                    'id' => $k->jurusan->id,
                    'nama_jurusan' => $k->jurusan->nama_jurusan,
                ] : null,
            ];
        })->values()->all();

        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
                'kelas' => [],
                'mata_pelajaran' => []
            ], 200);
        }

        // Filter by kelas_id if provided
        $kelasIds = $kelas->pluck('id');
        if ($request->has('kelas_id') && $request->kelas_id) {
            $kelasIds = $kelasIds->intersect([$request->kelas_id]);
            if ($kelasIds->isEmpty()) {
                return response()->json([
                    'message' => 'Kelas tidak ditemukan atau tidak diwalikan oleh Anda',
                    'kelas' => $kelasList,
                    'mata_pelajaran' => [],
                ], 200);
            }
        }

        // Get tahun ajaran aktif
        $tahunAjaran = \App\Models\TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
                'kelas' => $kelasList,
                'mata_pelajaran' => [],
            ], 200);
        }

        // Get all mata pelajaran from filtered classes
        $mataPelajaranIds = collect();
        foreach ($kelas->whereIn('id', $kelasIds) as $k) {
            $mapelIds = $k->mataPelajaran()
                ->where('mata_pelajaran.is_active', true)
                ->pluck('mata_pelajaran.id');
            $mataPelajaranIds = $mataPelajaranIds->merge($mapelIds);
        }

        // Get unique mata pelajaran
        $uniqueMapelIds = $mataPelajaranIds->unique()->values();
        
        if ($uniqueMapelIds->isEmpty()) {
            return response()->json([
                'kelas' => $kelasList,
                'mata_pelajaran' => [],
            ]);
        }

        $mataPelajaran = MataPelajaran::whereIn('id', $uniqueMapelIds)
            ->where('is_active', true)
            ->with(['guru.user'])
            ->orderBy('nama_mapel')
            ->get();

        // Get semester from request (default to 2 for SAS)
        $semester = $request->has('semester') && $request->semester ? $request->semester : '2';

        // Get tingkat from selected classes
        $tingkatList = \App\Models\Kelas::whereIn('id', $kelasIds)
            ->distinct('tingkat')
            ->pluck('tingkat')
            ->filter()
            ->values();

        // Calculate progress for each mata pelajaran
        $mataPelajaran->each(function($mapel) use ($kelasIds, $tahunAjaran, $semester, $tingkatList) {
            // Get all active students from filtered classes
            $totalSiswa = \App\Models\Siswa::whereIn('kelas_id', $kelasIds)
                ->where('status', 'aktif')
                ->count();

            // Get all active CP for this mata pelajaran that match semester, tingkat, and target (akhir_semester for SAS)
            $allCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mapel->id)
                ->where('is_active', true)
                ->where(function($query) use ($semester, $tingkatList) {
                    // Filter by semester: CP must have semester matching selected semester, or no semester (backward compatibility)
                    $query->where(function($q) use ($semester) {
                        $q->where('semester', $semester)
                          ->orWhereNull('semester');
                    });
                    
                    // Filter by tingkat: CP must have tingkat matching selected kelas tingkat, or no tingkat (backward compatibility)
                    if ($tingkatList->isNotEmpty()) {
                        $query->where(function($q) use ($tingkatList) {
                            $q->whereIn('tingkat', $tingkatList)
                              ->orWhereNull('tingkat');
                        });
                    }
                })
                ->where('target', 'akhir_semester') // Only CP with target 'akhir_semester' for SAS
                ->get();

            // Get SAS CP: dari $allCP dulu, kalau tidak ada (mis. SAS beda tingkat) cari SAS mapel ini tanpa filter tingkat
            $sasCP = $allCP->where('kode_cp', 'SAS')->first();
            if (!$sasCP) {
                $sasCP = $allCP->where('target', 'akhir_semester')->where('kode_cp', 'like', 'CP-%')->first();
            }
            if (!$sasCP) {
                $sasCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mapel->id)
                    ->where('kode_cp', 'SAS')
                    ->where('is_active', true)
                    ->where(function ($q) use ($semester) {
                        $q->where('semester', $semester)->orWhereNull('semester');
                    })
                    ->first();
            }

            // Get other CP (exclude STS/SAS kode_cp, but include all CP with akhir_semester target)
            $otherCP = $allCP->where('kode_cp', '!=', 'STS')
                            ->where('kode_cp', '!=', 'SAS')
                            ->where('target', 'akhir_semester');
            
            $progressData = [];
            $hasActiveCP = $otherCP->isNotEmpty();

            // Calculate progress for all active CP that have nilai in the selected semester (exclude STS and SAS)
            foreach ($otherCP as $cp) {
                $siswaSudahInput = Nilai::where('mata_pelajaran_id', $mapel->id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->where('semester', $semester)
                    ->where('capaian_pembelajaran_id', $cp->id)
                    ->whereHas('siswa', function($q) use ($kelasIds) {
                        $q->whereIn('kelas_id', $kelasIds)->where('status', 'aktif');
                    })
                    ->distinct('siswa_id')
                    ->count('siswa_id');

                $progressData[] = [
                    'capaian_pembelajaran_id' => $cp->id,
                    'kode_cp' => $cp->kode_cp,
                    'sudah_input' => $siswaSudahInput,
                    'total' => $totalSiswa,
                    'lengkap' => $siswaSudahInput == $totalSiswa && $totalSiswa > 0,
                ];
            }

            // Always show SAS if there are active CP (other than STS/SAS) that have nilai in the selected semester
            if ($sasCP || $hasActiveCP) {
                $sasSudahInput = 0;
                if ($sasCP) {
                    $sasSudahInput = Nilai::where('mata_pelajaran_id', $mapel->id)
                        ->where('tahun_ajaran_id', $tahunAjaran->id)
                        ->where('semester', $semester)
                        ->where('capaian_pembelajaran_id', $sasCP->id)
                        ->whereHas('siswa', function($q) use ($kelasIds) {
                            $q->whereIn('kelas_id', $kelasIds)->where('status', 'aktif');
                        })
                        ->distinct('siswa_id')
                        ->count('siswa_id');
                }

                $progressData[] = [
                    'capaian_pembelajaran_id' => $sasCP ? $sasCP->id : null,
                    'kode_cp' => 'SAS',
                    'sudah_input' => $sasSudahInput,
                    'total' => $totalSiswa,
                    'lengkap' => $sasSudahInput == $totalSiswa && $totalSiswa > 0,
                ];
            }

            $mapel->progress = [
                'total_siswa' => $totalSiswa,
                'total_cp' => count($progressData),
                'progress_data' => $progressData,
            ];
        });

        return response()->json([
            'kelas' => $kelasList,
            'mata_pelajaran' => $mataPelajaran,
        ]);
    }

    /**
     * Get detail STS for a specific mata pelajaran.
     * Returns list of students with their STS nilai status and grades.
     *
     * @param  Request  $request
     * @param  MataPelajaran  $mataPelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function stsDetail(Request $request, MataPelajaran $mataPelajaran)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isWaliKelas()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get active classes where user is wali kelas
        $kelas = $user->kelasAsWali();
        
        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
            ], 404);
        }

        // Verify that mata pelajaran is taught in user's classes
        $kelasIds = $kelas->pluck('id');
        $isMapelInKelas = $mataPelajaran->kelas()
            ->whereIn('kelas.id', $kelasIds)
            ->exists();
        
        if (!$isMapelInKelas) {
            return response()->json([
                'message' => 'Mata pelajaran tidak ditemukan di kelas yang Anda walikan',
            ], 404);
        }

        // Get tahun ajaran aktif
        $tahunAjaran = \App\Models\TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        // Get all active students from user's classes
        $siswaIds = collect();
        foreach ($kelas as $k) {
            $siswaIds = $siswaIds->merge($k->siswa()->where('status', 'aktif')->pluck('id'));
        }

        // Get semester from request (default to 1 for STS)
        $semester = $request->has('semester') && $request->semester ? $request->semester : '1';

        // Get all siswa with their nilai for STS
        $siswa = \App\Models\Siswa::whereIn('id', $siswaIds->unique())
            ->with(['user', 'kelas.jurusan'])
            ->orderBy('nama_lengkap')
            ->get();

        // Get tingkat from selected classes
        $tingkatList = \App\Models\Kelas::whereIn('id', $kelasIds)
            ->distinct('tingkat')
            ->pluck('tingkat')
            ->filter()
            ->values();

        // Get all active CP for this mata pelajaran that match semester, tingkat, and target (tengah_semester for STS)
        $allCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mataPelajaran->id)
            ->where('is_active', true)
            ->where(function($query) use ($semester, $tingkatList) {
                // Filter by semester: CP must have semester matching selected semester, or no semester (backward compatibility)
                $query->where(function($q) use ($semester) {
                    $q->where('semester', $semester)
                      ->orWhereNull('semester');
                });
                
                // Filter by tingkat: CP must have tingkat matching selected kelas tingkat, or no tingkat (backward compatibility)
                if ($tingkatList->isNotEmpty()) {
                    $query->where(function($q) use ($tingkatList) {
                        $q->whereIn('tingkat', $tingkatList)
                          ->orWhereNull('tingkat');
                    });
                }
            })
            ->where('target', 'tengah_semester') // Only CP with target 'tengah_semester' for STS
            ->get();

        // Get STS CP: dari $allCP dulu, kalau tidak ada (mis. STS beda tingkat) cari STS mapel ini tanpa filter tingkat
        $stsCP = $allCP->where('kode_cp', 'STS')->first();
        if (!$stsCP) {
            $stsCP = $allCP->where('target', 'tengah_semester')->where('kode_cp', 'like', 'CP-%')->first();
        }
        if (!$stsCP) {
            $stsCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mataPelajaran->id)
                ->where('kode_cp', 'STS')
                ->where('is_active', true)
                ->where(function ($q) use ($semester) {
                    $q->where('semester', $semester)->orWhereNull('semester');
                })
                ->first();
            if ($stsCP) {
                $allCP = $allCP->push($stsCP);
            }
        }

        // Get other CP (exclude STS/SAS kode_cp, but include all CP with tengah_semester target)
        $otherCP = $allCP->where('kode_cp', '!=', 'STS')
                        ->where('kode_cp', '!=', 'SAS')
                        ->where('target', 'tengah_semester');

        // Jika STS CP tetap tidak ada (mapel belum punya STS), tambah virtual STS untuk tampilan
        if ($otherCP->isNotEmpty() && !$stsCP) {
            $virtualSTSCP = (object)[
                'id' => null,
                'kode_cp' => 'STS',
                'deskripsi' => 'Sumatif Tengah Semester',
                'is_active' => true,
            ];
            $allCP = $allCP->push($virtualSTSCP);
        }

        // Get all nilai for this mata pelajaran, based on semester, tahun ajaran aktif
        $nilai = Nilai::where('mata_pelajaran_id', $mataPelajaran->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('semester', $semester)
            ->whereIn('siswa_id', $siswaIds->unique())
            ->with(['capaianPembelajaran', 'siswa.user'])
            ->get()
            ->groupBy('siswa_id');

        // Prepare response data
        $siswaData = $siswa->map(function($s) use ($nilai, $mataPelajaran, $allCP) {
            $siswaNilai = $nilai->get($s->id, collect());
            
            // Create nilai map by CP kode
            $nilaiByCP = [];
            foreach ($allCP as $cp) {
                $nilaiForCP = $siswaNilai->firstWhere('capaian_pembelajaran_id', $cp->id);
                $nilaiByCP[$cp->kode_cp] = $nilaiForCP ? [
                    'id' => $nilaiForCP->id,
                    'nilai' => $nilaiForCP->nilai_akhir ?? $nilaiForCP->nilai_sumatif_1,
                    'deskripsi' => $nilaiForCP->deskripsi,
                ] : null;
            }
            
            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'nama_lengkap' => $s->nama_lengkap,
                'kelas' => [
                    'id' => $s->kelas->id,
                    'nama_kelas' => $s->kelas->nama_kelas,
                    'jurusan' => $s->kelas->jurusan ? $s->kelas->jurusan->nama_jurusan : null,
                ],
                'sudah_mengerjakan' => collect($nilaiByCP)->filter()->isNotEmpty(),
                'nilai_by_cp' => $nilaiByCP,
            ];
        });

        // Get CP list for frontend (excluding SAS, STS at the end)
        $cpList = $allCP->map(function($cp) {
            return [
                'id' => $cp->id,
                'kode_cp' => $cp->kode_cp,
                'deskripsi' => $cp->deskripsi,
            ];
        })->sortBy(function($cp) {
            // Sort: CP first (alphabetically), then STS at the end
            if ($cp['kode_cp'] === 'STS') {
                return 'zzz'; // Put STS at the end
            }
            return $cp['kode_cp'];
        })->values();

        return response()->json([
            'mata_pelajaran' => [
                'id' => $mataPelajaran->id,
                'kode_mapel' => $mataPelajaran->kode_mapel,
                'nama_mapel' => $mataPelajaran->nama_mapel,
                'kkm' => $mataPelajaran->kkm,
            ],
            'tahun_ajaran' => [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
                'semester' => $tahunAjaran->semester,
            ],
            'capaian_pembelajaran' => $cpList,
            'siswa' => $siswaData,
        ]);
    }

    /**
     * Get detail SAS for a specific mata pelajaran.
     * Returns list of students with their SAS nilai status and grades.
     *
     * @param  Request  $request
     * @param  MataPelajaran  $mataPelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function sasDetail(Request $request, MataPelajaran $mataPelajaran)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isWaliKelas()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get active classes where user is wali kelas
        $kelas = $user->kelasAsWali();
        
        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
            ], 404);
        }

        // Verify that mata pelajaran is taught in user's classes
        $kelasIds = $kelas->pluck('id');
        $isMapelInKelas = $mataPelajaran->kelas()
            ->whereIn('kelas.id', $kelasIds)
            ->exists();
        
        if (!$isMapelInKelas) {
            return response()->json([
                'message' => 'Mata pelajaran tidak ditemukan di kelas yang Anda walikan',
            ], 404);
        }

        // Get tahun ajaran aktif
        $tahunAjaran = \App\Models\TahunAjaran::where('is_active', true)->first();
        
        if (!$tahunAjaran) {
            return response()->json([
                'message' => 'Tahun ajaran aktif tidak ditemukan',
            ], 404);
        }

        // Get all active students from user's classes
        $siswaIds = collect();
        foreach ($kelas as $k) {
            $siswaIds = $siswaIds->merge($k->siswa()->where('status', 'aktif')->pluck('id'));
        }

        // Get semester from request (default to 2 for SAS)
        $semester = $request->has('semester') && $request->semester ? $request->semester : '2';

        // Get all siswa with their nilai for SAS
        $siswa = \App\Models\Siswa::whereIn('id', $siswaIds->unique())
            ->with(['user', 'kelas.jurusan'])
            ->orderBy('nama_lengkap')
            ->get();

        // Get tingkat from selected classes
        $tingkatList = \App\Models\Kelas::whereIn('id', $kelasIds)
            ->distinct('tingkat')
            ->pluck('tingkat')
            ->filter()
            ->values();

        // Get all active CP for this mata pelajaran that match semester, tingkat, and target (akhir_semester for SAS)
        $allCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mataPelajaran->id)
            ->where('is_active', true)
            ->where(function($query) use ($semester, $tingkatList) {
                // Filter by semester: CP must have semester matching selected semester, or no semester (backward compatibility)
                $query->where(function($q) use ($semester) {
                    $q->where('semester', $semester)
                      ->orWhereNull('semester');
                });
                
                // Filter by tingkat: CP must have tingkat matching selected kelas tingkat, or no tingkat (backward compatibility)
                if ($tingkatList->isNotEmpty()) {
                    $query->where(function($q) use ($tingkatList) {
                        $q->whereIn('tingkat', $tingkatList)
                          ->orWhereNull('tingkat');
                    });
                }
            })
            ->where('target', 'akhir_semester') // Only CP with target 'akhir_semester' for SAS
            ->get();

        // Get SAS CP: dari $allCP dulu, kalau tidak ada (mis. SAS beda tingkat) cari SAS mapel ini tanpa filter tingkat
        $sasCP = $allCP->where('kode_cp', 'SAS')->first();
        if (!$sasCP) {
            $sasCP = $allCP->where('target', 'akhir_semester')->where('kode_cp', 'like', 'CP-%')->first();
        }
        if (!$sasCP) {
            $sasCP = \App\Models\CapaianPembelajaran::where('mata_pelajaran_id', $mataPelajaran->id)
                ->where('kode_cp', 'SAS')
                ->where('is_active', true)
                ->where(function ($q) use ($semester) {
                    $q->where('semester', $semester)->orWhereNull('semester');
                })
                ->first();
            if ($sasCP) {
                $allCP = $allCP->push($sasCP);
            }
        }

        // Get other CP (exclude STS/SAS kode_cp, but include all CP with akhir_semester target)
        $otherCP = $allCP->where('kode_cp', '!=', 'STS')
                        ->where('kode_cp', '!=', 'SAS')
                        ->where('target', 'akhir_semester');

        // Jika SAS CP tetap tidak ada (mapel belum punya SAS), tambah virtual SAS untuk tampilan
        if ($otherCP->isNotEmpty() && !$sasCP) {
            $virtualSASCP = (object)[
                'id' => null,
                'kode_cp' => 'SAS',
                'deskripsi' => 'Sumatif Akhir Semester',
                'is_active' => true,
            ];
            $allCP = $allCP->push($virtualSASCP);
        }

        // Get all nilai for this mata pelajaran, based on semester, tahun ajaran aktif
        $nilai = Nilai::where('mata_pelajaran_id', $mataPelajaran->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('semester', $semester)
            ->whereIn('siswa_id', $siswaIds->unique())
            ->with(['capaianPembelajaran', 'siswa.user'])
            ->get()
            ->groupBy('siswa_id');

        // Prepare response data
        $siswaData = $siswa->map(function($s) use ($nilai, $mataPelajaran, $allCP) {
            $siswaNilai = $nilai->get($s->id, collect());
            
            // Create nilai map by CP kode
            $nilaiByCP = [];
            foreach ($allCP as $cp) {
                $nilaiForCP = $siswaNilai->firstWhere('capaian_pembelajaran_id', $cp->id);
                $nilaiByCP[$cp->kode_cp] = $nilaiForCP ? [
                    'id' => $nilaiForCP->id,
                    'nilai' => $nilaiForCP->nilai_akhir ?? $nilaiForCP->nilai_sumatif_1,
                    'deskripsi' => $nilaiForCP->deskripsi,
                ] : null;
            }
            
            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'nama_lengkap' => $s->nama_lengkap,
                'kelas' => [
                    'id' => $s->kelas->id,
                    'nama_kelas' => $s->kelas->nama_kelas,
                    'jurusan' => $s->kelas->jurusan ? $s->kelas->jurusan->nama_jurusan : null,
                ],
                'sudah_mengerjakan' => collect($nilaiByCP)->filter()->isNotEmpty(),
                'nilai_by_cp' => $nilaiByCP,
            ];
        });

        // Get CP list for frontend (excluding STS, SAS at the end)
        $cpList = $allCP->map(function($cp) {
            return [
                'id' => $cp->id,
                'kode_cp' => $cp->kode_cp,
                'deskripsi' => $cp->deskripsi,
            ];
        })->sortBy(function($cp) {
            // Sort: CP first (alphabetically), then SAS at the end
            if ($cp['kode_cp'] === 'SAS') {
                return 'zzz'; // Put SAS at the end
            }
            return $cp['kode_cp'];
        })->values();

        return response()->json([
            'mata_pelajaran' => [
                'id' => $mataPelajaran->id,
                'kode_mapel' => $mataPelajaran->kode_mapel,
                'nama_mapel' => $mataPelajaran->nama_mapel,
                'kkm' => $mataPelajaran->kkm,
            ],
            'tahun_ajaran' => [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
                'semester' => $tahunAjaran->semester,
            ],
            'capaian_pembelajaran' => $cpList,
            'siswa' => $siswaData,
        ]);
    }

    /**
     * Get list of P5 projects for wali kelas (projek yang punya siswa dari kelas yang diwalikan).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function p5(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->isWaliKelas()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->load('guru');
        $kelas = $user->kelasAsWali();
        $kelas = $kelas ? $kelas->filter() : collect();
        if ($kelas->isEmpty()) {
            return response()->json([
                'message' => 'Anda belum ditugaskan sebagai wali kelas',
                'kelas' => [],
                'projek' => [],
            ], 200);
        }

        $kelasIds = $kelas->pluck('id');
        if ($request->filled('kelas_id')) {
            $requestKelasId = (int) $request->kelas_id;
            if ($kelasIds->contains($requestKelasId)) {
                $kelasIds = collect([$requestKelasId]);
            }
        }
        $siswaIds = \App\Models\Siswa::whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->pluck('id');

        $kelasList = $kelas->map(function ($k) {
            return [
                'id' => $k->id,
                'nama_kelas' => $k->nama_kelas,
                'tingkat' => $k->tingkat,
                'jurusan_id' => $k->jurusan_id,
                'jurusan' => $k->jurusan ? [
                    'id' => $k->jurusan->id,
                    'nama_jurusan' => $k->jurusan->nama_jurusan,
                ] : null,
            ];
        })->values()->all();

        if ($siswaIds->isEmpty()) {
            return response()->json([
                'kelas' => $kelasList,
                'projek' => [],
            ], 200);
        }

        $p5IdsFromPeserta = DB::table('p5_siswa')
            ->whereIn('siswa_id', $siswaIds)
            ->distinct()
            ->pluck('p5_id');

        $p5IdsFromKelompok = DB::table('p5_kelompok_siswa')
            ->join('p5_kelompok', 'p5_kelompok_siswa.p5_kelompok_id', '=', 'p5_kelompok.id')
            ->whereIn('p5_kelompok_siswa.siswa_id', $siswaIds)
            ->distinct()
            ->pluck('p5_kelompok.p5_id');

        $p5Ids = $p5IdsFromPeserta->merge($p5IdsFromKelompok)->unique()->filter()->values();

        $projek = P5::whereIn('id', $p5Ids)
            ->with(['koordinator.user', 'tahunAjaran'])
            ->orderBy('judul')
            ->get()
            ->map(function ($p5) {
                return [
                    'id' => $p5->id,
                    'judul' => $p5->judul,
                    'tema' => $p5->tema,
                    'dimensi' => $p5->dimensi,
                    'tahun_ajaran' => $p5->tahunAjaran ? [
                        'id' => $p5->tahunAjaran->id,
                        'tahun' => $p5->tahunAjaran->tahun,
                    ] : null,
                    'koordinator' => $p5->koordinator ? [
                        'id' => $p5->koordinator->id,
                        'nama_lengkap' => $p5->koordinator->nama_lengkap,
                    ] : null,
                ];
            });

        return response()->json([
            'kelas' => $kelasList,
            'projek' => $projek,
        ]);
    }

    /**
     * Get detail P5: list siswa (dari kelas wali) dengan fasilitator dan progress nilai per sub elemen.
     *
     * @param  Request  $request
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function p5Detail(Request $request, P5 $p5)
    {
        $user = Auth::user();
        if (! $user || ! $user->isWaliKelas()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $kelasIds = $user->kelasAsWali()->pluck('id');
        if ($request->filled('kelas_id')) {
            $requestKelasId = (int) $request->kelas_id;
            if ($kelasIds->contains($requestKelasId)) {
                $kelasIds = collect([$requestKelasId]);
            }
        }
        $siswaIdsWali = \App\Models\Siswa::whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->pluck('id');

        if ($siswaIdsWali->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada siswa di kelas yang Anda walikan',
                'p5' => null,
                'elemen_sub' => [],
                'siswa' => [],
            ], 200);
        }

        $p5->load(['koordinator.user', 'tahunAjaran', 'kelompok.guru.user', 'kelompok.siswa.kelas.jurusan']);
        $elemenSub = $p5->elemen_sub ?? [];
        $subElemenList = [];
        if (is_array($elemenSub) && count($elemenSub) > 0) {
            $subElemenList = array_values(array_unique(array_filter(array_map(function ($es) {
                return $es['sub_elemen'] ?? ($es['sub_elemen'] ?? '');
            }, $elemenSub))));
        }
        $totalSubElemen = count($subElemenList);

        $siswaMap = [];
        foreach ($p5->kelompok as $kelompok) {
            $fasilitator = $kelompok->guru ? [
                'id' => $kelompok->guru->id,
                'nama_lengkap' => $kelompok->guru->nama_lengkap,
            ] : null;
            foreach ($kelompok->siswa as $s) {
                if (! $siswaIdsWali->contains($s->id)) {
                    continue;
                }
                if (! isset($siswaMap[$s->id])) {
                    $siswaMap[$s->id] = [
                        'id' => $s->id,
                        'nis' => $s->nis,
                        'nama_lengkap' => $s->nama_lengkap,
                        'kelas' => $s->kelas ? [
                            'id' => $s->kelas->id,
                            'nama_kelas' => $s->kelas->nama_kelas,
                            'jurusan' => $s->kelas->jurusan ? $s->kelas->jurusan->nama_jurusan : null,
                        ] : null,
                        'fasilitator' => $fasilitator,
                    ];
                }
            }
        }
        $pesertaIds = DB::table('p5_siswa')->where('p5_id', $p5->id)->pluck('siswa_id');
        foreach ($pesertaIds as $sid) {
            if (! $siswaIdsWali->contains($sid)) {
                continue;
            }
            if (isset($siswaMap[$sid])) {
                continue;
            }
            $s = \App\Models\Siswa::with(['kelas.jurusan'])->find($sid);
            if ($s) {
                $siswaMap[$sid] = [
                    'id' => $s->id,
                    'nis' => $s->nis,
                    'nama_lengkap' => $s->nama_lengkap,
                    'kelas' => $s->kelas ? [
                        'id' => $s->kelas->id,
                        'nama_kelas' => $s->kelas->nama_kelas,
                        'jurusan' => $s->kelas->jurusan ? $s->kelas->jurusan->nama_jurusan : null,
                    ] : null,
                    'fasilitator' => $p5->koordinator ? [
                        'id' => $p5->koordinator->id,
                        'nama_lengkap' => $p5->koordinator->nama_lengkap,
                    ] : null,
                ];
            }
        }

        $nilaiRows = NilaiP5::where('p5_id', $p5->id)
            ->whereIn('siswa_id', array_keys($siswaMap))
            ->whereNotNull('sub_elemen')
            ->whereIn('sub_elemen', $subElemenList)
            ->get();

        $nilaiCountBySiswa = $nilaiRows->groupBy('siswa_id')->map(fn ($rows) => $rows->pluck('sub_elemen')->unique()->count());
        $nilaiPerSiswa = [];
        foreach ($nilaiRows as $row) {
            if (! isset($nilaiPerSiswa[$row->siswa_id])) {
                $nilaiPerSiswa[$row->siswa_id] = [];
            }
            $nilaiPerSiswa[$row->siswa_id][$row->sub_elemen] = $row->nilai;
        }

        $siswaList = [];
        foreach ($siswaMap as $sid => $row) {
            $sudah = (int) ($nilaiCountBySiswa[$sid] ?? 0);
            $siswaList[] = array_merge($row, [
                'progress' => [
                    'sudah' => $sudah,
                    'total' => $totalSubElemen,
                    'lengkap' => $totalSubElemen > 0 && $sudah >= $totalSubElemen,
                ],
            ]);
        }
        usort($siswaList, fn ($a, $b) => strcmp($a['nama_lengkap'], $b['nama_lengkap']));

        return response()->json([
            'p5' => [
                'id' => $p5->id,
                'judul' => $p5->judul,
                'tema' => $p5->tema,
                'dimensi' => $p5->dimensi,
                'tahun_ajaran' => $p5->tahunAjaran ? [
                    'id' => $p5->tahunAjaran->id,
                    'tahun' => $p5->tahunAjaran->tahun,
                ] : null,
            ],
            'elemen_sub' => $elemenSub,
            'siswa' => $siswaList,
            'nilai_per_siswa' => $nilaiPerSiswa,
        ]);
    }
}

