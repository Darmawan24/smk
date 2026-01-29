<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureUserIsWaliKelas
 *
 * Allows access only for users with role guru who have at least one active
 * WaliKelas assignment. Kepala sekolah is excluded.
 */
class EnsureUserIsWaliKelas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        if ($user->role !== 'guru') {
            return response()->json([
                'message' => 'Unauthorized. Only guru designated as wali kelas may access.',
            ], 403);
        }

        if (!$user->guru || !$user->guru->waliKelasAktif()->exists()) {
            return response()->json([
                'message' => 'Unauthorized. You are not assigned as wali kelas.',
            ], 403);
        }

        return $next($request);
    }
}
