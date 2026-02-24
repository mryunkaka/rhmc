<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Validasi Controller
 * Mirror dari: legacy/dashboard/validasi.php + validasi_action.php
 */
class ValidasiController extends Controller
{
    /**
     * Display validasi page
     * Mirror dari: legacy/dashboard/validasi.php
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Guard role (kecuali staff)
        if ($userRole === 'staff') {
            abort(403, 'Akses ditolak');
        }

        // Query semua user
        $users = DB::table('user_rh')
            ->select('id', 'full_name', 'role', 'position', 'is_verified', 'created_at')
            ->orderBy('is_verified', 'asc')  // 0 (belum valid) di atas
            ->orderBy('created_at', 'desc')  // yang terbaru lebih dulu
            ->get();

        $pageTitle = 'Validasi User';

        return view('validasi.index', compact(
            'pageTitle',
            'users'
        ));
    }

    /**
     * Approve user verification
     * Mirror dari: legacy/dashboard/validasi_action.php?act=approve
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Guard role
        if ($userRole === 'staff') {
            abort(403, 'Akses ditolak');
        }

        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            return redirect()->route('dashboard.validasi');
        }

        DB::table('user_rh')
            ->where('id', $id)
            ->update(['is_verified' => 1]);

        return redirect()->route('dashboard.validasi')
            ->with('success', 'User berhasil diverifikasi!');
    }

    /**
     * Reject/cancel user verification
     * Mirror dari: legacy/dashboard/validasi_action.php?act=reject
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Guard role
        if ($userRole === 'staff') {
            abort(403, 'Akses ditolak');
        }

        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            return redirect()->route('dashboard.validasi');
        }

        DB::table('user_rh')
            ->where('id', $id)
            ->update(['is_verified' => 0]);

        return redirect()->route('dashboard.validasi')
            ->with('success', 'Verifikasi user berhasil dibatalkan!');
    }
}
