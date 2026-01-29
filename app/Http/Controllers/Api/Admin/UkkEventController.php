<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UkkEvent;
use Illuminate\Http\Request;

class UkkEventController extends Controller
{
    public function lookup(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'jurusan_id' => 'required|exists:jurusan,id',
            'kelas_id' => 'required|exists:kelas,id',
        ]);
        $e = UkkEvent::with(['tahunAjaran', 'jurusan', 'kelas', 'pengujiInternal.user'])
            ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
            ->where('jurusan_id', $request->jurusan_id)
            ->where('kelas_id', $request->kelas_id)
            ->first();
        if (!$e) {
            return response()->json(['message' => 'Data UKK tidak ditemukan untuk tahun ajaran, jurusan, dan kelas ini.'], 404);
        }
        return response()->json($e);
    }

    public function index(Request $request)
    {
        $query = UkkEvent::with(['tahunAjaran', 'jurusan', 'kelas', 'pengujiInternal.user']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('jurusan', fn ($q) => $q->where('nama_jurusan', 'like', "%{$s}%"))
                    ->orWhereHas('kelas', fn ($q) => $q->where('nama_kelas', 'like', "%{$s}%"))
                    ->orWhere('nama_du_di', 'like', "%{$s}%");
            });
        }
        if ($request->filled('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }
        if ($request->filled('jurusan_id')) {
            $query->where('jurusan_id', $request->jurusan_id);
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $data = $query->orderBy('tanggal_ujian', 'desc')->paginate($request->get('per_page', 15));
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'jurusan_id' => 'required|exists:jurusan,id',
            'kelas_id' => 'required|exists:kelas,id',
            'nama_du_di' => 'nullable|string|max:255',
            'tanggal_ujian' => 'required|date',
            'penguji_internal_id' => 'required|exists:guru,id',
            'penguji_eksternal' => 'nullable|string|max:255',
        ]);

        $kelas = \App\Models\Kelas::find($v['kelas_id']);
        if (!$kelas || $kelas->jurusan_id != $v['jurusan_id']) {
            return response()->json(['message' => 'Kelas tidak sesuai dengan jurusan yang dipilih.'], 422);
        }

        $exists = UkkEvent::where('tahun_ajaran_id', $v['tahun_ajaran_id'])
            ->where('jurusan_id', $v['jurusan_id'])
            ->where('kelas_id', $v['kelas_id'])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Data UKK untuk tahun ajaran, jurusan, dan kelas ini sudah ada.',
            ], 422);
        }

        $event = UkkEvent::create($v);
        $event->load(['tahunAjaran', 'jurusan', 'kelas', 'pengujiInternal.user']);
        return response()->json($event, 201);
    }

    public function show(UkkEvent $ukk_event)
    {
        $ukk_event->load(['tahunAjaran', 'jurusan', 'kelas', 'pengujiInternal.user', 'ukk.siswa.user']);
        return response()->json($ukk_event);
    }

    public function update(Request $request, UkkEvent $ukk_event)
    {
        $v = $request->validate([
            'tahun_ajaran_id' => 'sometimes|required|exists:tahun_ajaran,id',
            'jurusan_id' => 'sometimes|required|exists:jurusan,id',
            'kelas_id' => 'sometimes|required|exists:kelas,id',
            'nama_du_di' => 'nullable|string|max:255',
            'tanggal_ujian' => 'sometimes|required|date',
            'penguji_internal_id' => 'sometimes|required|exists:guru,id',
            'penguji_eksternal' => 'nullable|string|max:255',
        ]);

        $ta = $v['tahun_ajaran_id'] ?? $ukk_event->tahun_ajaran_id;
        $ju = $v['jurusan_id'] ?? $ukk_event->jurusan_id;
        $ke = $v['kelas_id'] ?? $ukk_event->kelas_id;
        if (array_key_exists('kelas_id', $v) || array_key_exists('jurusan_id', $v)) {
            $kelas = \App\Models\Kelas::find($ke);
            if (!$kelas || (int) $kelas->jurusan_id !== (int) $ju) {
                return response()->json(['message' => 'Kelas tidak sesuai dengan jurusan yang dipilih.'], 422);
            }
        }
        if (array_key_exists('tahun_ajaran_id', $v) || array_key_exists('jurusan_id', $v) || array_key_exists('kelas_id', $v)) {
            $other = UkkEvent::where('tahun_ajaran_id', $ta)
                ->where('jurusan_id', $ju)
                ->where('kelas_id', $ke)
                ->where('id', '!=', $ukk_event->id)
                ->exists();
            if ($other) {
                return response()->json(['message' => 'Data UKK untuk tahun ajaran, jurusan, dan kelas ini sudah ada.'], 422);
            }
        }

        $ukk_event->update($v);
        $ukk_event->load(['tahunAjaran', 'jurusan', 'kelas', 'pengujiInternal.user']);
        return response()->json($ukk_event);
    }

    public function destroy(UkkEvent $ukk_event)
    {
        if ($ukk_event->ukk()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus Data UKK yang sudah memiliki Nilai UKK. Hapus nilai terlebih dahulu.',
            ], 422);
        }
        $ukk_event->delete();
        return response()->json(['message' => 'Data UKK berhasil dihapus']);
    }
}
