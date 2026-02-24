<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Dashboard Controller
 * Mirror dari: legacy/dashboard/index.php + legacy/dashboard/dashboard_data.php
 */
class DashboardController extends Controller
{
    // Middleware applied via routes, not constructor

    /**
     * Display dashboard
     * Mirror dari: legacy/dashboard/index.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get date range data
        $dateRangeData = getDateRangeData();
        $rangeStart = $dateRangeData['rangeStart'];
        $rangeEnd = $dateRangeData['rangeEnd'];
        $rangeLabel = $dateRangeData['rangeLabel'];
        $weeks = $dateRangeData['weeks'];

        // Get user data dari session
        $userRh = session('user_rh');
        $medicName = $userRh['name'] ?? '';
        $medicJabatan = $userRh['position'] ?? '';
        $medicRole = $userRh['role'] ?? 'Staff';

        // Generate avatar data
        $avatarInitials = initialsFromName($medicName);
        $avatarColor = avatarColorFromName($medicName);

        // Load dashboard data
        $dashboard = $this->getDashboardData($rangeStart, $rangeEnd, $weeks);

        $pageTitle = 'Dashboard | Farmasi EMS';

        return view('dashboard.index', compact(
            'dashboard',
            'rangeLabel',
            'pageTitle',
            'medicName',
            'medicJabatan',
            'medicRole',
            'avatarInitials',
            'avatarColor'
        ));
    }

    /**
     * Get dashboard statistics data
     * Mirror dari: legacy/dashboard/dashboard_data.php
     *
     * @param  \Carbon\Carbon  $rangeStart
     * @param  \Carbon\Carbon  $rangeEnd
     * @param  array  $weeks
     * @return array
     */
    private function getDashboardData($rangeStart, $rangeEnd, $weeks)
    {
        // =====================================================
        // STATISTIC CARDS (FARMASI + ITEM + PAKET)
        // Mirror dari: dashboard_data.php lines 53-77
        // =====================================================
        $statFarmasi = DB::selectOne("
            SELECT
                COUNT(DISTINCT medic_name) AS total_medic,
                COUNT(DISTINCT TRIM(UPPER(consumer_name))) AS total_consumer,
                COUNT(id) AS total_transaksi,

                -- Total item
                SUM(qty_bandage + qty_ifaks + qty_painkiller) AS total_item,

                -- Total per item
                SUM(qty_bandage)    AS total_bandage,
                SUM(qty_painkiller) AS total_painkiller,
                SUM(qty_ifaks)      AS total_ifaks,

                -- Total paket terjual
                SUM(package_name = 'Paket A') AS total_paket_a,
                SUM(package_name = 'Paket B') AS total_paket_b,

                -- Keuangan
                SUM(price) AS total_income,
                SUM(price * 0.4) AS total_bonus,
                SUM(price * 0.6) AS company_profit
            FROM sales
            WHERE created_at BETWEEN ? AND ?
        ", [$rangeStart, $rangeEnd]);

        // =====================================================
        // REKAP MEDIS
        // Mirror dari: dashboard_data.php lines 82-95
        // =====================================================
        $rekapMedis = DB::selectOne("
            SELECT
                SUM(UPPER(medicine_usage) LIKE '%P3K%')      AS total_p3k,
                SUM(UPPER(medicine_usage) LIKE '%BANDAGE%')  AS total_bandage,
                SUM(UPPER(medicine_usage) LIKE '%GAUZE%')    AS total_gauze,
                SUM(UPPER(medicine_usage) LIKE '%IODINE%')   AS total_iodine,
                SUM(UPPER(medicine_usage) LIKE '%SYRINGE%')  AS total_syringe,

                SUM(operasi_tingkat = 'plastik') AS operasi_plastik,
                SUM(operasi_tingkat = 'ringan')  AS operasi_ringan,
                SUM(operasi_tingkat = 'berat')   AS operasi_berat
            FROM ems_sales
            WHERE created_at BETWEEN ? AND ?
        ", [$rangeStart, $rangeEnd]);

        // =====================================================
        // WEEKLY WINNER (WEEK 1 - 4)
        // Mirror dari: dashboard_data.php lines 100-143
        // =====================================================
        $weeklyWinner = [];
        $chartWeekly  = [
            'labels' => [],
            'values' => []
        ];

        foreach ($weeks as $key => $w) {
            // Convert Carbon to string for formatTanggalIndo
            $startStr = $w['start'] instanceof Carbon ? $w['start']->toDateTimeString() : $w['start'];
            $endStr = $w['end'] instanceof Carbon ? $w['end']->toDateTimeString() : $w['end'];

            $labelTanggal = formatTanggalIndo($startStr) . ' - ' . formatTanggalIndo($endStr);

            // Get weekly income
            $weeklyIncome = DB::selectOne("
                SELECT SUM(price) AS total
                FROM sales
                WHERE created_at BETWEEN ? AND ?
            ", [$w['start'], $w['end']]);

            $totalIncomeWeek = (float)($weeklyIncome->total ?? 0);

            // Get weekly winner
            $winner = DB::selectOne("
                SELECT medic_name, SUM(price) AS total
                FROM sales
                WHERE created_at BETWEEN ? AND ?
                GROUP BY medic_name
                ORDER BY SUM(price) DESC
                LIMIT 1
            ", [$w['start'], $w['end']]);

            $totalSales = (float)($winner->total ?? 0);

            $weeklyWinner[$labelTanggal] = [
                'medic'       => $winner->medic_name ?? '-',
                'total_sales' => $totalSales,
                'bonus_40'    => $totalSales * 0.4
            ];

            $chartWeekly['labels'][] = $labelTanggal;
            $chartWeekly['values'][] = $totalIncomeWeek;
        }

        // =====================================================
        // MONTHLY WINNER
        // Mirror dari: dashboard_data.php lines 148-176
        // =====================================================
        $currentMonthStart = now('Asia/Jakarta')->startOfMonth()->format('Y-m-d 00:00:00');
        $currentMonthEnd   = now('Asia/Jakarta')->endOfMonth()->format('Y-m-d 23:59:59');

        $lastMonthStart = now('Asia/Jakarta')->subMonth()->startOfMonth()->format('Y-m-d 00:00:00');
        $lastMonthEnd   = now('Asia/Jakarta')->subMonth()->endOfMonth()->format('Y-m-d 23:59:59');

        $monthlyCurrent = DB::selectOne("
            SELECT medic_name, SUM(price) AS total
            FROM sales
            WHERE created_at BETWEEN ? AND ?
            GROUP BY medic_name
            ORDER BY total DESC
            LIMIT 1
        ", [$currentMonthStart, $currentMonthEnd]);

        $monthlyLast = DB::selectOne("
            SELECT medic_name, SUM(price) AS total
            FROM sales
            WHERE created_at BETWEEN ? AND ?
            GROUP BY medic_name
            ORDER BY total DESC
            LIMIT 1
        ", [$lastMonthStart, $lastMonthEnd]);

        // =====================================================
        // TOP EARNING MEDIC (BONUS TERBESAR)
        // Mirror dari: dashboard_data.php lines 181-188
        // =====================================================
        $topEarning = DB::selectOne("
            SELECT medic_name, SUM(price * 0.4) AS bonus
            FROM sales
            WHERE created_at BETWEEN ? AND ?
            GROUP BY medic_name
            ORDER BY bonus DESC
            LIMIT 1
        ", [$rangeStart, $rangeEnd]);

        // =====================================================
        // FINAL DASHBOARD ARRAY (VIEW ONLY)
        // Mirror dari: dashboard_data.php lines 193-248
        // =====================================================
        $totalMonthlyCurrent = (float)($monthlyCurrent->total ?? 0);
        $totalMonthlyLast    = (float)($monthlyLast->total ?? 0);

        return [
            // FARMASI
            'total_medic'     => (int)($statFarmasi->total_medic ?? 0),
            'total_consumer'  => (int)($statFarmasi->total_consumer ?? 0),
            'total_transaksi' => (int)($statFarmasi->total_transaksi ?? 0),
            'total_item'      => (int)($statFarmasi->total_item ?? 0),
            'total_income'    => (float)($statFarmasi->total_income ?? 0),
            'total_bonus'     => (float)($statFarmasi->total_bonus ?? 0),
            'company_profit'  => (float)($statFarmasi->company_profit ?? 0),
            'total_bandage'    => (int)($statFarmasi->total_bandage ?? 0),
            'total_painkiller' => (int)($statFarmasi->total_painkiller ?? 0),
            'total_ifaks'      => (int)($statFarmasi->total_ifaks ?? 0),

            'total_paket_a' => (int)($statFarmasi->total_paket_a ?? 0),
            'total_paket_b' => (int)($statFarmasi->total_paket_b ?? 0),

            // MEDIS
            'rekap_medis' => [
                'p3k'             => (int)($rekapMedis->total_p3k ?? 0),
                'bandage'         => (int)($rekapMedis->total_bandage ?? 0),
                'gauze'           => (int)($rekapMedis->total_gauze ?? 0),
                'iodine'          => (int)($rekapMedis->total_iodine ?? 0),
                'syringe'         => (int)($rekapMedis->total_syringe ?? 0),
                'operasi_plastik' => (int)($rekapMedis->operasi_plastik ?? 0),
                'operasi_ringan'  => (int)($rekapMedis->operasi_ringan ?? 0),
                'operasi_berat'   => (int)($rekapMedis->operasi_berat ?? 0),
            ],

            // Weekly Ranking
            'weekly_winner' => $weeklyWinner,

            // Monthly Winner
            'monthly_current' => [
                'medic'    => $monthlyCurrent->medic_name ?? '-',
                'bonus_40' => $totalMonthlyCurrent * 0.4
            ],

            'monthly_last' => [
                'medic'    => $monthlyLast->medic_name ?? '-',
                'bonus_40' => $totalMonthlyLast * 0.4
            ],

            // Top Earning Medic
            'top_earning' => [
                'medic' => $topEarning->medic_name ?? '-',
                'bonus' => (float)($topEarning->bonus ?? 0)
            ],

            // Charts
            'chart_weekly' => $chartWeekly
        ];
    }

    /**
     * Generate initials from name
     * Mirror dari: legacy/config/helpers.php
     *
     * @param  string  $name
     * @return string
     */
    private function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return mb_strtoupper(
                mb_substr($parts[0], 0, 1) .
                    mb_substr($parts[1], 0, 1)
            );
        }
        return mb_strtoupper(mb_substr($parts[0], 0, 2));
    }

    /**
     * Generate avatar color from name
     * Mirror dari: legacy/config/helpers.php
     *
     * @param  string  $name
     * @return string
     */
    private function avatarColorFromName(string $name): string
    {
        $hash = crc32(mb_strtolower(trim($name)));
        $hue  = $hash % 360;
        return "hsl($hue, 70%, 45%)";
    }
}
