<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\P5;
use Illuminate\Http\Request;

/**
 * P5Controller for Admin
 * 
 * Handles P5 project management for admin (full access)
 */
class P5Controller extends Controller
{
    /**
     * Display a listing of P5 projects.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = P5::with(['koordinator.user', 'tahunAjaran']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tema', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->has('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        if ($request->has('koordinator_id')) {
            $query->where('koordinator_id', $request->koordinator_id);
        }

        $p5 = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($p5);
    }

    /**
     * Store a newly created P5 project.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'tema' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'koordinator_id' => 'required|exists:guru,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
        ]);

        $p5 = P5::create($request->all());

        // Reload with relationships
        $p5->load(['koordinator.user', 'tahunAjaran']);

        return response()->json([
            'message' => 'Projek P5 berhasil dibuat',
            'data' => $p5,
        ], 201);
    }

    /**
     * Display the specified P5 project.
     *
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(P5 $p5)
    {
        $p5->load(['koordinator.user', 'tahunAjaran', 'nilaiP5.siswa.user', 'nilaiP5.dimensi']);

        return response()->json($p5);
    }

    /**
     * Update the specified P5 project.
     *
     * @param  Request  $request
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, P5 $p5)
    {
        $request->validate([
            'tema' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string',
            'koordinator_id' => 'sometimes|required|exists:guru,id',
            'tahun_ajaran_id' => 'sometimes|required|exists:tahun_ajaran,id',
        ]);

        $p5->update($request->all());

        // Reload with relationships
        $p5->load(['koordinator.user', 'tahunAjaran']);

        return response()->json([
            'message' => 'Projek P5 berhasil diperbarui',
            'data' => $p5,
        ]);
    }

    /**
     * Remove the specified P5 project.
     *
     * @param  P5  $p5
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(P5 $p5)
    {
        $p5->delete();

        return response()->json([
            'message' => 'Projek P5 berhasil dihapus',
        ]);
    }
}

