<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AbsensiEmsController extends Controller
{
    /**
     * Jam Kerja EMS Page
     * Mirror of: legacy/dashboard/absensi_ems.php
     */
    public function index(Request $request)
    {
        $sessionUser = session('user_rh');
        $userId = $sessionUser['id'] ?? null;
        $rangeType = $request->get('range', 'current_week');

        if ($rangeType === 'last_week') {
            $rangeStart = Carbon::now()->subWeek()->startOfWeek()->toDateTimeString();
            $rangeEnd = Carbon::now()->subWeek()->endOfWeek()->toDateTimeString();
            $rangeLabel = 'Minggu Sebelumnya';
        }
        elseif ($rangeType === 'custom' && $request->start && $request->end) {
            $rangeStart = $request->start . ' 00:00:00';
            $rangeEnd = $request->end . ' 23:59:59';
            $rangeLabel = 'Custom';
        }
        else {
            $rangeStart = Carbon::now()->startOfWeek()->toDateTimeString();
            $rangeEnd = Carbon::now()->endOfWeek()->toDateTimeString();
            $rangeLabel = 'Minggu Ini';
        }

        // Summary Week
        $totalSecondsWeek = (int)DB::table('user_farmasi_sessions')
            ->where('user_id', $userId)
            ->whereBetween('session_start', [$rangeStart, $rangeEnd])
            ->sum('duration_seconds');

        $totalWeek = $this->formatSeconds($totalSecondsWeek);

        // Today Sessions
        $todaySessions = DB::table('user_farmasi_sessions')
            ->select('session_start', 'session_end', 'duration_seconds', 'end_reason', DB::raw('UNIX_TIMESTAMP(session_start) as start_timestamp'))
            ->where('user_id', $userId)
            ->whereDate('session_start', Carbon::today())
            ->orderBy('session_start', 'asc')
            ->get();

        // Total All
        $totalSecondsAll = (int)DB::table('user_farmasi_sessions')
            ->where('user_id', $userId)
            ->sum('duration_seconds');

        $totalAll = $this->formatSeconds($totalSecondsAll);

        // Leaderboard
        $leaderboard = DB::table('user_farmasi_sessions')
            ->select('medic_name', 'medic_jabatan', DB::raw('SUM(duration_seconds) as total_seconds'), DB::raw('COUNT(*) as total_sesi'))
            ->whereBetween('session_start', [$rangeStart, $rangeEnd])
            ->groupBy('user_id', 'medic_name', 'medic_jabatan')
            ->orderBy('total_seconds', 'desc')
            ->get();

        return view('dashboard.absensi_ems', [
            'pageTitle' => 'Jam Kerja EMS',
            'medicName' => $sessionUser['name'] ?? 'User',
            'medicPos' => $sessionUser['position'] ?? '-',
            'userRole' => $sessionUser['role'] ?? 'Staff',
            'rangeLabel' => $rangeLabel,
            'rangeType' => $rangeType,
            'totalWeek' => $totalWeek,
            'totalAll' => $totalAll,
            'todaySessions' => $todaySessions,
            'leaderboard' => $leaderboard
        ]);
    }

    private function formatSeconds($seconds)
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
