<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RankingController extends Controller
{
    /**
     * Display Ranking Medis
     * Mirror dari legacy/dashboard/ranking.php
     */
    public function index(Request $request)
    {
        $user = session('user_rh');

        // ======================================================
        // DATE RANGE LOGIC (MIRROR PHP NATIVE)
        // ======================================================
        $rangeType = $request->get('range', 'current_week');

        // Default: Minggu Ini (Sama dengan week4 di date_range logic)
        $now = Carbon::now('Asia/Jakarta');
        $mondayThisWeek = $now->copy()->startOfWeek()->setTime(0, 0, 0);
        $rangeStart = $mondayThisWeek->toDateTimeString();
        $rangeEnd = $mondayThisWeek->copy()->endOfWeek()->setTime(23, 59, 59)->toDateTimeString();
        $rangeLabel = $mondayThisWeek->format('d M Y') . ' – ' . $mondayThisWeek->copy()->endOfWeek()->format('d M Y');

        if ($rangeType === 'last_week') {
            // Minggu sebelumnya (Senin–Minggu)
            $rangeStart = Carbon::parse('monday last week', 'Asia/Jakarta')->startOfDay()->toDateTimeString();
            $rangeEnd = Carbon::parse('sunday last week', 'Asia/Jakarta')->endOfDay()->toDateTimeString();
            $rangeLabel = 'Minggu Sebelumnya';
        }
        elseif ($rangeType === 'custom' && $request->get('start') && $request->get('end')) {
            $rangeStart = $request->get('start') . ' 00:00:00';
            $rangeEnd = $request->get('end') . ' 23:59:59';
            $rangeLabel = 'Custom: ' . $request->get('start') . ' s/d ' . $request->get('end');
        }

        // ======================================================
        // QUERY RANKING (MIRROR SQL)
        // ======================================================
        $medicRanking = DB::select("
            SELECT 
                medic_name,
                medic_jabatan,
                COUNT(*) AS total_transaksi,
                SUM(qty_bandage + qty_ifaks + qty_painkiller) AS total_item,
                SUM(price) AS total_rupiah
            FROM sales
            WHERE created_at BETWEEN ? AND ?
            GROUP BY medic_name, medic_jabatan
            ORDER BY total_rupiah DESC
        ", [$rangeStart, $rangeEnd]);

        // ======================================================
        // RETURN VIEW
        // ======================================================
        return view('dashboard.ranking', [
            'pageTitle' => 'Ranking Medis',
            'rangeLabel' => $rangeLabel,
            'medicRanking' => $medicRanking,
            'medicName' => $user['name'] ?? 'User',
            'medicPos' => $user['position'] ?? '-',
            'range' => $rangeType,
            'start' => $request->get('start'),
            'end' => $request->get('end'),
        ]);
    }
}
