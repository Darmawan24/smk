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
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);
        $query = UkkEvent::with(['tahunAjaran', 'jurusan', 'kelas', 'pengujiInternal.user'])
            ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
            ->where('jurusan_id', $request->jurusan_id);
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        } else {
            $query->whereNull('kelas_id');
        }
        $e = $query->first();
        if (!$e) {
            return response()->json(['message' => 'Data UKK tidak ditemukan untuk tahun ajaran dan jurusan ini.'], 404);
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
            'kelas_id' => 'nullable|exists:kelas,id',
            'nama_du_di' => 'nullable|string|max:255',
            'tanggal_ujian' => 'required|date',
            'penguji_internal_id' => 'required|exists:guru,id',
            'penguji_eksternal' => 'nullable|string|max:255',
        ]);

        $kelasId = $v['kelas_id'] ?? null;
        if ($kelasId) {
            $kelas = \App\Models\Kelas::find($kelasId);
            if (!$kelas || (int) $kelas->jurusan_id !== (int) $v['jurusan_id']) {
                return response()->json(['message' => 'Kelas tidak sesuai dengan jurusan yang dipilih.'], 422);
            }
        }

        $existsQuery = UkkEvent::where('tahun_ajaran_id', $v['tahun_ajaran_id'])
            ->where('jurusan_id', $v['jurusan_id']);
        if ($kelasId) {
            $existsQuery->where('kelas_id', $kelasId);
        } else {
            $existsQuery->whereNull('kelas_id');
        }
        if ($existsQuery->exists()) {
            return response()->json([
                'message' => 'Data UKK untuk tahun ajaran dan jurusan ini sudah ada.',
            ], 422);
        }

        $v['kelas_id'] = $kelasId;
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
            'kelas_id' => 'nullable|exists:kelas,id',
            'nama_du_di' => 'nullable|string|max:255',
            'tanggal_ujian' => 'sometimes|required|date',
            'penguji_internal_id' => 'sometimes|required|exists:guru,id',
            'penguji_eksternal' => 'nullable|string|max:255',
        ]);

        $ta = $v['tahun_ajaran_id'] ?? $ukk_event->tahun_ajaran_id;
        $ju = $v['jurusan_id'] ?? $ukk_event->jurusan_id;
        $ke = array_key_exists('kelas_id', $v) ? $v['kelas_id'] : $ukk_event->kelas_id;

        if ($ke) {
            $kelas = \App\Models\Kelas::find($ke);
            if (!$kelas || (int) $kelas->jurusan_id !== (int) $ju) {
                return response()->json(['message' => 'Kelas tidak sesuai dengan jurusan yang dipilih.'], 422);
            }
        }

        if (array_key_exists('tahun_ajaran_id', $v) || array_key_exists('jurusan_id', $v) || array_key_exists('kelas_id', $v)) {
            $otherQuery = UkkEvent::where('tahun_ajaran_id', $ta)
                ->where('jurusan_id', $ju)
                ->where('id', '!=', $ukk_event->id);
            if ($ke) {
                $otherQuery->where('kelas_id', $ke);
            } else {
                $otherQuery->whereNull('kelas_id');
            }
            if ($otherQuery->exists()) {
                return response()->json(['message' => 'Data UKK untuk tahun ajaran dan jurusan ini sudah ada.'], 422);
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
