<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Pastikan user sudah terautentikasi
        if (!Auth::check()) {
            // Jika API, kembalikan 401 Unauthorized
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            // Jika web, redirect ke login
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Periksa apakah role user ada dalam daftar role yang diizinkan
        // $roles bisa berupa 'admin' atau 'admin,super_admin'
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Jika tidak memiliki role yang diperlukan
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Forbidden. Insufficient role.'], 403);
        }
        
        // Untuk web, bisa redirect ke halaman forbidden atau dashboard user biasa
        abort(403, 'Forbidden. Insufficient role.');
    }
}
