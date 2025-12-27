<?php

namespace App\Http\Controllers\Api\Guru;

use App\Http\Controllers\Controller;
use App\Models\TujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use Illuminate\Http\Request;

/**
 * TujuanPembelajaranController for Guru
 * 
 * Handles TP (Tujuan Pembelajaran) management for teachers
 */
class TujuanPembelajaranController extends Controller
{
    /**
     * Display a listing of TP for a CP.
     *
     * @param  Request  $request
     * @param  CapaianPembelajaran  $capaianPembelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(CapaianPembelajaran $capaianPembelajaran)
    {
        $tp = $capaianPembelajaran->tujuanPembelajaran()
                                  ->orderBy('kode_tp')
                                  ->get();

        return response()->json([
            'capaian_pembelajaran' => $capaianPembelajaran->load('mataPelajaran'),
            'tujuan_pembelajaran' => $tp,
        ]);
    }

    /**
     * Store a newly created TP.
     *
     * @param  Request  $request
     * @param  CapaianPembelajaran  $capaianPembelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, CapaianPembelajaran $capaianPembelajaran)
    {
        $request->validate([
            'kode_tp' => 'required|string|max:50',
            'deskripsi' => 'required|string',
        ]);

        $tp = TujuanPembelajaran::create([
            'capaian_pembelajaran_id' => $capaianPembelajaran->id,
            'kode_tp' => $request->kode_tp,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'message' => 'Tujuan Pembelajaran berhasil ditambahkan',
            'data' => $tp->load('capaianPembelajaran.mataPelajaran'),
        ], 201);
    }

    /**
     * Display the specified TP.
     *
     * @param  TujuanPembelajaran  $tujuanPembelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(TujuanPembelajaran $tujuanPembelajaran)
    {
        $tujuanPembelajaran->load(['capaianPembelajaran.mataPelajaran']);

        return response()->json($tujuanPembelajaran);
    }

    /**
     * Update the specified TP.
     *
     * @param  Request  $request
     * @param  TujuanPembelajaran  $tujuanPembelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, TujuanPembelajaran $tujuanPembelajaran)
    {
        $request->validate([
            'kode_tp' => 'sometimes|required|string|max:50',
            'deskripsi' => 'sometimes|required|string',
        ]);

        $tujuanPembelajaran->update($request->only([
            'kode_tp',
            'deskripsi',
        ]));

        return response()->json([
            'message' => 'Tujuan Pembelajaran berhasil diperbarui',
            'data' => $tujuanPembelajaran->load(['capaianPembelajaran.mataPelajaran']),
        ]);
    }

    /**
     * Remove the specified TP.
     *
     * @param  TujuanPembelajaran  $tujuanPembelajaran
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TujuanPembelajaran $tujuanPembelajaran)
    {
        $tujuanPembelajaran->delete();

        return response()->json([
            'message' => 'Tujuan Pembelajaran berhasil dihapus',
        ]);
    }
}

