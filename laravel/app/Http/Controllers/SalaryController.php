<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\DateRange; // Menggunakan helper DateRange yang sudah ada

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $user = Session::get('user_rh');
        $userRole = strtolower(trim($user['role'] ?? ''));
        $userName = $user['full_name'] ?? '';
        $isStaff = ($userRole === 'staff');

        $range = $request->query('range', 'week3');
        $from = $request->query('from');
        $to = $request->query('to');

        // Menggunakan helper DateRange (mirror legacy date_range.php)
        $dateRange = DateRange::getRange($range, $from, $to);
        $rangeStart = $dateRange['start'];
        $rangeEnd = $dateRange['end'];
        $rangeLabel = $dateRange['label'];

        // 1. QUERY REKAP GAJI
        $rekap = DB::selectOne("
            SELECT
                COUNT(DISTINCT medic_name) AS total_medis,
                SUM(total_transaksi) AS total_transaksi,
                SUM(total_item) AS total_item,
                SUM(total_rupiah) AS total_rupiah,
                SUM(bonus_40) AS total_bonus
            FROM salary
            WHERE period_end BETWEEN ? AND ?
        ", [$rangeStart, $rangeEnd]);

        $rekap = [
            'total_medis' => (int)($rekap->total_medis ?? 0),
            'total_transaksi' => (int)($rekap->total_transaksi ?? 0),
            'total_item' => (int)($rekap->total_item ?? 0),
            'total_rupiah' => (int)($rekap->total_rupiah ?? 0),
            'total_bonus' => (int)($rekap->total_bonus ?? 0),
        ];

        // 2. QUERY TOTAL SUDAH DIBAYARKAN
        $totalPaidBonus = DB::table('salary')
            ->whereBetween('period_end', [$rangeStart, $rangeEnd])
            ->where('status', 'paid')
            ->sum('bonus_40');

        $sisaBonus = $rekap['total_bonus'] - $totalPaidBonus;

        // 3. QUERY DAFTAR GAJI
        if ($isStaff) {
            $salary = DB::table('salary')
                ->where('medic_name', $userName)
                ->orderBy('period_end', 'desc')
                ->get();
        }
        else {
            $salary = DB::table('salary')
                ->whereBetween('period_end', [$rangeStart, $rangeEnd])
                ->orderBy('period_end', 'desc')
                ->get();
        }

        $pageTitle = 'Gaji Mingguan';

        return view('dashboard.salary', compact(
            'salary', 'rekap', 'totalPaidBonus', 'sisaBonus',
            'rangeLabel', 'rangeStart', 'rangeEnd', 'isStaff', 'userRole', 'pageTitle'
        ));
    }

    public function payProcess(Request $request)
    {
        $request->validate([
            'salary_id' => 'required|integer',
            'pay_method' => 'required|in:direct,titip',
            'titip_to' => 'nullable|integer'
        ]);

        $user = Session::get('user_rh');
        $salaryId = $request->salary_id;
        $method = $request->pay_method;
        $titipTo = $request->titip_to;

        DB::beginTransaction();
        try {
            $salary = DB::table('salary')->where('id', $salaryId)->first();
            if (!$salary) {
                return response()->json(['success' => false, 'message' => 'Data gaji tidak ditemukan']);
            }

            if ($salary->status === 'paid') {
                return response()->json(['success' => false, 'message' => 'Gaji sudah dibayar']);
            }

            $updateData = [
                'status' => 'paid',
                'paid_by' => $user['full_name'] ?? 'System',
                'paid_at' => now(),
                'updated_at' => now()
            ];

            if ($method === 'titip' && $titipTo) {
                $targetUser = DB::table('user_rh')->where('id', $titipTo)->first();
                if ($targetUser) {
                    $updateData['paid_by'] .= " (Titip ke: " . $targetUser->full_name . ")";
                }
            }

            DB::table('salary')->where('id', $salaryId)->update($updateData);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pembayaran berhasil diproses']);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    public function generateManual(Request $request)
    {
        // Mirror legacy/dashboard/gaji_generate_manual.php
        $user = Session::get('user_rh');
        $userRole = strtolower(trim($user['role'] ?? ''));

        $allowedRoles = ['vice director', 'director'];
        if (!in_array($userRole, $allowedRoles, true)) {
            return response('Akses ditolak', 403);
        }

        // Logic Generate (Mirror from legacy)
        $firstSale = DB::table('ems_sales')->min(DB::raw('DATE(created_at)'));

        if (!$firstSale) {
            return redirect()->route('dashboard.salary')->with('msg', 'nosales');
        }

        $startDate = new \DateTime($firstSale);
        $startDate->modify('monday this week');

        $today = new \DateTime();
        $today->modify('monday this week');

        $now = new \DateTime();
        $generated = 0;

        while ($startDate <= $today) {
            $periodStart = clone $startDate;
            $periodEnd = (clone $startDate)->modify('+6 days');

            $periodStartStr = $periodStart->format('Y-m-d');
            $periodEndStr = $periodEnd->format('Y-m-d');

            // Skip minggu berjalan
            if ($periodEnd >= $now) {
                $startDate->modify('+7 days');
                continue;
            }

            // Cek sudah ada
            $exists = DB::table('salary')
                ->where('period_start', $periodStartStr)
                ->where('period_end', $periodEndStr)
                ->exists();

            if ($exists) {
                $startDate->modify('+7 days');
                continue;
            }

            // Ambil sales dari sales table (legacy)
            $rows = DB::table('sales')
                ->select(
                    'medic_name',
                    DB::raw('MAX(medic_jabatan) AS medic_jabatan'),
                    DB::raw('COUNT(*) AS total_transaksi'),
                    DB::raw('SUM(qty_bandage + qty_ifaks + qty_painkiller) AS total_item'),
                    DB::raw('SUM(price) AS total_rupiah')
                )
                ->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$periodStartStr, $periodEndStr])
                ->groupBy('medic_name')
                ->get();

            if ($rows->isEmpty()) {
                $startDate->modify('+7 days');
                continue;
            }

            foreach ($rows as $r) {
                DB::table('salary')->insert([
                    'medic_name' => $r->medic_name,
                    'medic_jabatan' => $r->medic_jabatan,
                    'period_start' => $periodStartStr,
                    'period_end' => $periodEndStr,
                    'total_transaksi' => $r->total_transaksi,
                    'total_item' => $r->total_item,
                    'total_rupiah' => $r->total_rupiah,
                    'bonus_40' => floor($r->total_rupiah * 0.4),
                    'status' => 'unpaid',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $generated++;
            $startDate->modify('+7 days');
        }

        return redirect()->route('dashboard.salary')->with('generated', $generated);
    }
}
