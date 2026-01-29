<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ukk;
use App\Models\UkkEvent;
use App\Models\Jurusan;
use Illuminate\Http\Request;

/**
 * UkkController
 *
 * Handles Nilai UKK (scores) – event data is in UkkEventController.
 */
class UkkController extends Controller
{
    /**
     * Display a listing of UKK.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Ukk::with(['siswa.user', 'jurusan', 'kelas', 'pengujiInternal.user', 'tahunAjaran']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('siswa', function ($q) use ($search) {
                      $q->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                  })
                  ->orWhereHas('kelas', function ($q) use ($search) {
                      $q->where('nama_kelas', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('jurusan_id')) {
            $query->where('jurusan_id', $request->jurusan_id);
        }

        if ($request->has('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->has('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        if ($request->has('predikat')) {
            $query->where('predikat', $request->predikat);
        }

        $ukk = $query->orderBy('tanggal_ujian', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($ukk);
    }

    /**
     * Store Nilai UKK (link siswa to event and save scores).
     */
    public function store(Request $request)
    {
        $request->validate([
            'jurusan_id' => 'required|exists:jurusan,id',
            'kelas_id' => 'required|exists:kelas,id',
            'siswa_id' => 'required|exists:siswa,id',
            'nilai_teori' => 'nullable|integer|min:0|max:100',
            'nilai_praktek' => 'nullable|integer|min:0|max:100',
        ]);

        $event = UkkEvent::where('jurusan_id', $request->jurusan_id)
            ->where('kelas_id', $request->kelas_id)
            ->with('tahunAjaran')
            ->orderBy('tanggal_ujian', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$event) {
            return response()->json([
                'message' => 'Data UKK tidak ditemukan untuk jurusan dan kelas ini. Buat Data UKK terlebih dahulu.',
            ], 422);
        }

        $siswa = \App\Models\Siswa::findOrFail($request->siswa_id);
        if ($siswa->kelas_id != $request->kelas_id || $siswa->kelas->jurusan_id != $request->jurusan_id) {
            return response()->json(['message' => 'Siswa tidak berada di jurusan/kelas yang dipilih.'], 422);
        }

        if (Ukk::where('ukk_event_id', $event->id)->where('siswa_id', $request->siswa_id)->exists()) {
            return response()->json(['message' => 'Nilai UKK untuk siswa ini sudah ada pada Data UKK yang sama.'], 422);
        }

        $ukk = Ukk::create([
            'ukk_event_id' => $event->id,
            'siswa_id' => $request->siswa_id,
            'jurusan_id' => $event->jurusan_id,
            'kelas_id' => $event->kelas_id,
            'tahun_ajaran_id' => $event->tahun_ajaran_id,
            'nama_du_di' => $event->nama_du_di,
            'tanggal_ujian' => $event->tanggal_ujian,
            'penguji_internal_id' => $event->penguji_internal_id,
            'penguji_eksternal' => $event->penguji_eksternal,
            'nilai_teori' => $request->nilai_teori ? (int) $request->nilai_teori : null,
            'nilai_praktek' => $request->nilai_praktek ? (int) $request->nilai_praktek : null,
        ]);

        $ukk->load(['siswa.user', 'jurusan', 'kelas', 'pengujiInternal.user', 'tahunAjaran']);
        return response()->json($ukk, 201);
    }

    /**
     * Display the specified UKK.
     *
     * @param  Ukk  $ukk
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Ukk $ukk)
    {
        $ukk->load(['siswa.user', 'jurusan', 'pengujiInternal.user', 'tahunAjaran']);

        return response()->json($ukk);
    }

    /**
     * Update Nilai UKK (scores only).
     */
    public function update(Request $request, Ukk $ukk)
    {
        $request->validate([
            'nilai_teori' => 'nullable|integer|min:0|max:100',
            'nilai_praktek' => 'nullable|integer|min:0|max:100',
        ]);

        $up = [];
        if ($request->has('nilai_teori')) {
            $up['nilai_teori'] = $request->nilai_teori === '' || $request->nilai_teori === null ? null : (int) $request->nilai_teori;
        }
        if ($request->has('nilai_praktek')) {
            $up['nilai_praktek'] = $request->nilai_praktek === '' || $request->nilai_praktek === null ? null : (int) $request->nilai_praktek;
        }
        $ukk->update($up);

        $ukk->load(['siswa.user', 'jurusan', 'kelas', 'pengujiInternal.user', 'tahunAjaran']);
        return response()->json($ukk);
    }

    /**
     * Remove the specified UKK.
     *
     * @param  Ukk  $ukk
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Ukk $ukk)
    {
        $ukk->delete();

        return response()->json([
            'message' => 'UKK berhasil dihapus',
        ]);
    }

    /**
     * Get UKK by jurusan.
     *
     * @param  Jurusan  $jurusan
     * @return \Illuminate\Http\JsonResponse
     */
    public function byJurusan(Jurusan $jurusan)
    {
        $ukk = Ukk::with(['siswa.user', 'pengujiInternal.user', 'tahunAjaran'])
                  ->where('jurusan_id', $jurusan->id)
                  ->orderBy('tanggal_ujian', 'desc')
                  ->get();

        return response()->json($ukk);
    }
}

