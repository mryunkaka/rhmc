<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Package;
use App\Models\FarmasiActivity;
use App\Models\UserFarmasiStatus;
use App\Models\UserFarmasiSession;
use App\Models\UserRh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\DateRange;

class RekapFarmasiController extends Controller
{
    /**
     * Dashboard Rekap Farmasi
     */
    public function index(Request $request)
    {
        $user = session('user_rh');
        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expired');
        }

        $medicName = $user['name'];
        $medicJabatan = $user['position'];
        $medicRole = $user['role'];

        // Access check: Trainee not allowed
        if (strtolower(trim($medicJabatan)) === 'trainee') {
            return response()->view('errors.403_farmasi', [], 403);
        }

        // Date range
        $range = request('range', 'week4');
        $rangeData = DateRange::getRange($range, request('from'), request('to'));
        $rangeStart = $rangeData['start'];
        $rangeEnd = $rangeData['end'];
        $rangeLabel = $rangeData['label'];
        $weeks = $rangeData['weeks'];

        // Packages
        $packages = Package::orderBy('name')->get();
        $paketAB = [];
        $bandagePkg = [];
        $ifaksPkg = [];
        $painPkg = [];

        foreach ($packages as $p) {
            $name = strtoupper($p->name);
            if (str_starts_with($name, 'PAKET A') || str_starts_with($name, 'PAKET B')) {
                $paketAB[] = $p;
            }
            elseif ($p->bandage_qty > 0 && $p->ifaks_qty == 0 && $p->painkiller_qty == 0) {
                $bandagePkg[] = $p;
            }
            elseif ($p->ifaks_qty > 0 && $p->bandage_qty == 0 && $p->painkiller_qty == 0) {
                $ifaksPkg[] = $p;
            }
            elseif ($p->painkiller_qty > 0 && $p->bandage_qty == 0 && $p->ifaks_qty == 0) {
                $painPkg[] = $p;
            }
        }

        // Consumer names for datalist
        $consumerNames = Sale::distinct()->orderBy('consumer_name')->pluck('consumer_name');

        // Single medic stats (for current user)
        $singleMedicStats = Sale::where('medic_user_id', $user['id'])
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->selectRaw('medic_name, medic_jabatan, COUNT(*) as total_transaksi, SUM(qty_bandage + qty_ifaks + qty_painkiller) as total_item, SUM(price) as total_harga')
            ->groupBy('medic_user_id', 'medic_name', 'medic_jabatan')
            ->first();

        if ($singleMedicStats) {
            $singleMedicStats->bonus_40 = floor($singleMedicStats->total_harga * 0.4);
        }

        // Today stats
        $todayStats = Sale::where('medic_user_id', $user['id'])
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('COUNT(*) as total_transaksi, SUM(qty_bandage + qty_ifaks + qty_painkiller) as total_item, SUM(price) as total_harga')
            ->first();
        if ($todayStats) {
            $todayStats->bonus_40 = floor(($todayStats->total_harga ?? 0) * 0.4);
        }

        // Filtered sales
        $showAll = $request->get('show_all') === '1';
        $query = Sale::whereBetween('created_at', [$rangeStart, $rangeEnd]);
        if (!$showAll) {
            $query->where('medic_user_id', $user['id']);
        }
        $filteredSales = $query->orderBy('created_at', 'desc')->get();

        // Online Medics (Realtime fallback)
        $onlineMedics = $this->getOnlineMedicsData();

        // ➕ Missing variables for Legacy JS
        $pricePerPcs = [
            'bandage'    => 0,
            'ifaks'      => 0,
            'painkiller' => 0,
        ];
        foreach ($packages as $p) {
            if ($p->bandage_qty > 0 && $p->ifaks_qty == 0 && $p->painkiller_qty == 0) {
                $pricePerPcs['bandage'] = (int)($p->price / max(1, $p->bandage_qty));
            }
            if ($p->ifaks_qty > 0 && $p->bandage_qty == 0 && $p->painkiller_qty == 0) {
                $pricePerPcs['ifaks'] = (int)($p->price / max(1, $p->ifaks_qty));
            }
            if ($p->painkiller_qty > 0 && $p->bandage_qty == 0 && $p->ifaks_qty == 0) {
                $pricePerPcs['painkiller'] = (int)($p->price / max(1, $p->painkiller_qty));
            }
        }

        $todayDate = date('Y-m-d');
        $dailyTotalsRows = Sale::selectRaw('consumer_name, COALESCE(SUM(qty_bandage),0) AS total_bandage, COALESCE(SUM(qty_ifaks),0) AS total_ifaks, COALESCE(SUM(qty_painkiller),0) AS total_painkiller')
            ->whereDate('created_at', $todayDate)
            ->groupBy('consumer_name')
            ->get();

        $dailyTotalsJS = [];
        foreach ($dailyTotalsRows as $row) {
            $key = mb_strtolower(trim($row->consumer_name));
            $dailyTotalsJS[$key] = [
                'bandage'    => (int)$row->total_bandage,
                'ifaks'      => (int)$row->total_ifaks,
                'painkiller' => (int)$row->total_painkiller,
            ];
        }

        $detailRows = Sale::select('consumer_name', 'medic_name', 'package_name', 'created_at', 'qty_bandage', 'qty_ifaks', 'qty_painkiller')
            ->whereDate('created_at', $todayDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $dailyDetailJS = [];
        foreach ($detailRows as $row) {
            $key = mb_strtolower(trim($row->consumer_name));
            if (!isset($dailyDetailJS[$key])) {
                $dailyDetailJS[$key] = [];
            }
            $dailyDetailJS[$key][] = [
                'medic'      => $row->medic_name,
                'package'    => $row->package_name,
                'time'       => formatTanggalID($row->created_at),
                'bandage'    => (int)$row->qty_bandage,
                'ifaks'      => (int)$row->qty_ifaks,
                'painkiller' => (int)$row->qty_painkiller,
            ];
        }

        $packagesById = [];
        foreach ($packages as $p) {
            $packagesById[$p->id] = [
                'name'       => $p->name,
                'price'      => (int)$p->price,
                'bandage'    => (int)$p->bandage_qty,
                'ifaks'      => (int)$p->ifaks_qty,
                'painkiller' => (int)$p->painkiller_qty,
            ];
        }

        $flashMessages = session('flash_messages', []);
        $flashWarnings = session('flash_warnings', []);
        $flashErrors = session('flash_errors', []);
        session()->forget(['flash_messages', 'flash_warnings', 'flash_errors']);

        return view('dashboard.rekap_farmasi', [
            'pageTitle' => 'Rekap Farmasi',
            'medicName' => $medicName,
            'medicJabatan' => $medicJabatan,
            'medicRole' => $medicRole,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'rangeLabel' => $rangeLabel,
            'range' => $range,
            'weeks' => $weeks,
            'paketAB' => $paketAB,
            'bandagePkg' => $bandagePkg,
            'ifaksPkg' => $ifaksPkg,
            'painPkg' => $painPkg,
            'consumerNames' => $consumerNames,
            'singleMedicStats' => $singleMedicStats,
            'todayStats' => $todayStats,
            'filteredSales' => $filteredSales,
            'showAll' => $showAll,
            'onlineMedics' => $onlineMedics,
            'pricePerPcs' => $pricePerPcs,
            'dailyTotalsJS' => $dailyTotalsJS,
            'dailyDetailJS' => $dailyDetailJS,
            'packagesById' => $packagesById,
            'flashMessages' => $flashMessages,
            'flashWarnings' => $flashWarnings,
            'flashErrors' => $flashErrors,
            'shouldClearForm' => session('clear_form', false)
        ]);
    }

    /**
     * Store Transaction
     */
    public function store(Request $request)
    {
        $user = session('user_rh');
        $userId = $user['id'];

        // Cooldown check
        $lastTx = session('last_tx_ts', 0);
        if (time() - $lastTx < 10) {
            return redirect()->back()->with('flash_errors', ['Mohon tunggu ' . (10 - (time() - $lastTx)) . ' detik sebelum input transaksi berikutnya.']);
        }

        // Status check
        $status = UserFarmasiStatus::where('user_id', $userId)->first();
        if (!$status || $status->status !== 'online') {
            return redirect()->back()->with('flash_errors', ['Anda berstatus OFFLINE.']);
        }

        $consumerName = ucwords(strtolower(trim($request->consumer_name)));
        $pkgIds = array_filter([
            $request->package_main,
            $request->package_bandage,
            $request->package_ifaks,
            $request->package_painkiller
        ]);

        if (empty($pkgIds)) {
            return redirect()->back()->with('flash_errors', ['Pilih minimal satu paket.']);
        }

        $packages = Package::whereIn('id', $pkgIds)->get();
        if ($packages->count() !== count($pkgIds)) {
            return redirect()->back()->with('flash_errors', ['Ada paket yang tidak ditemukan.']);
        }

        // Daily limits check
        $totalsToday = Sale::where('consumer_name', $consumerName)
            ->whereDate('created_at', Carbon::today())
            ->first();
        if ($totalsToday) {
            return redirect()->back()->with('flash_errors', ['Konsumen ini sudah melakukan transaksi hari ini.']);
        }

        // Transaction Logic
        DB::transaction(function () use ($request, $user, $consumerName, $packages) {
            $postedToken = $request->tx_token;
            foreach ($packages as $p) {
                $txHash = hash('sha256', $postedToken . '|' . $p->id);
                Sale::create([
                    'consumer_name' => $consumerName,
                    'medic_name' => $user['name'],
                    'medic_user_id' => $user['id'],
                    'medic_jabatan' => $user['position'],
                    'package_id' => $p->id,
                    'package_name' => $p->name,
                    'price' => $p->price,
                    'qty_bandage' => $p->bandage_qty,
                    'qty_ifaks' => $p->ifaks_qty,
                    'qty_painkiller' => $p->painkiller_qty,
                    'tx_hash' => $txHash,
                    'created_at' => now(),
                ]);
            }

            // Log activity
            $totalB = $packages->sum('bandage_qty');
            $totalI = $packages->sum('ifaks_qty');
            $totalP = $packages->sum('painkiller_qty');
            $totalPrice = $packages->sum('price');

            $items = [];
            if ($totalB > 0)
                $items[] = "$totalB Bandage";
            if ($totalI > 0)
                $items[] = "$totalI IFAKS";
            if ($totalP > 0)
                $items[] = "$totalP Painkiller";

            FarmasiActivity::create([
                'activity_type' => 'transaction',
                'medic_user_id' => $user['id'],
                'medic_name' => $user['name'],
                'description' => "Transaksi: $consumerName - " . implode(', ', $items) . " ($ " . number_format($totalPrice) . ")",
            ]);

            // Ensure session started
            $activeSession = UserFarmasiSession::where('user_id', $user['id'])
                ->whereNull('session_end')
                ->first();
            if (!$activeSession) {
                UserFarmasiSession::create([
                    'user_id' => $user['id'],
                    'medic_name' => $user['name'],
                    'medic_jabatan' => $user['position'],
                    'session_start' => now(),
                ]);
            }

            // Update status
            UserFarmasiStatus::updateOrCreate(
            ['user_id' => $user['id']],
            [
                'status' => 'online',
                'last_activity_at' => now(),
                'last_confirm_at' => now()
            ]
            );
        });

        session(['last_tx_ts' => time()]);

        return redirect()->back()->with('flash_messages', ["Transaksi {$consumerName} berhasil disimpan."])->with('clear_form', true);
    }

    /**
     * AJAX: Get Online Medics
     */
    public function getOnlineMedics()
    {
        return response()->json($this->getOnlineMedicsData());
    }

    private function getOnlineMedicsData()
    {
        $medics = UserFarmasiStatus::where('status', 'online')
            ->with(['user' => function ($q) {
            $q->select('id', 'full_name', 'position');
        }])
            ->get();

        $data = [];
        $mondayThisWeek = Carbon::now('Asia/Jakarta')->startOfWeek();
        $sundayThisWeek = Carbon::now('Asia/Jakarta')->endOfWeek();

        foreach ($medics as $m) {
            if (!$m->user)
                continue;

            $dailyTx = Sale::where('medic_user_id', $m->user_id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            $dailyIncome = Sale::where('medic_user_id', $m->user_id)
                ->whereDate('created_at', Carbon::today())
                ->sum('price');

            $weeklyTx = Sale::where('medic_user_id', $m->user_id)
                ->whereBetween('created_at', [$mondayThisWeek, $sundayThisWeek])
                ->count();

            // Mirror legacy: weekly online seconds must include ACTIVE sessions (session_end is NULL)
            // so the timer doesn't reset to 0 on refresh while user is still online.
            $weeklySeconds = (int) (UserFarmasiSession::where('user_id', $m->user_id)
                ->whereBetween('session_start', [$mondayThisWeek, $sundayThisWeek])
                ->selectRaw("
                    COALESCE(SUM(
                        CASE
                            WHEN session_end IS NULL THEN TIMESTAMPDIFF(SECOND, session_start, NOW())
                            ELSE COALESCE(duration_seconds, 0)
                        END
                    ), 0) AS total_seconds
                ")
                ->value('total_seconds') ?? 0);

            $hours = floor($weeklySeconds / 3600);
            $minutes = floor(($weeklySeconds % 3600) / 60);
            $seconds = $weeklySeconds % 60;

            $data[] = [
                'user_id' => $m->user_id,
                'medic_name' => $m->user->full_name,
                'medic_jabatan' => $m->user->position,
                'total_transaksi' => $dailyTx,
                'total_pendapatan' => $dailyIncome,
                'bonus_40' => floor($dailyIncome * 0.4),
                'weekly_transaksi' => $weeklyTx,
                'weekly_online_seconds' => $weeklySeconds,
                'weekly_online_text' => "{$hours}j {$minutes}m {$seconds}d"
            ];
        }

        // Sort by total_transaksi ASC, then total_pendapatan ASC
        usort($data, function ($a, $b) {
            if ($a['total_transaksi'] === $b['total_transaksi']) {
                return $a['total_pendapatan'] <=> $b['total_pendapatan'];
            }
            return $a['total_transaksi'] <=> $b['total_transaksi'];
        });

        return $data;
    }

    /**
     * AJAX: Get Activity Feed
     */
    public function getActivities()
    {
        $activities = FarmasiActivity::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($a) {
            return [
            'id' => $a->id,
            'type' => $a->activity_type,
            'medic_name' => $a->medic_name,
            'description' => $a->description,
            'timestamp' => $a->created_at->timestamp
            ];
        });

        return response()->json($activities);
    }

    /**
     * AJAX: Toggle Status
     */
    public function toggleStatus(Request $request)
    {
        $user = session('user_rh');
        $status = $request->status; // online / offline

        $mStatus = UserFarmasiStatus::where('user_id', $user['id'])->first();
        if (!$mStatus) {
            $mStatus = new UserFarmasiStatus();
            $mStatus->user_id = $user['id'];
        }

        if ($status === 'online') {
            $mStatus->status = 'online';
            $mStatus->last_activity_at = now();
            $mStatus->last_confirm_at = now();
            $mStatus->save();

            // Mirror legacy: only create a session if there is no active session yet
            $activeSession = UserFarmasiSession::where('user_id', $user['id'])
                ->whereNull('session_end')
                ->first();
            if (! $activeSession) {
                UserFarmasiSession::create([
                    'user_id' => $user['id'],
                    'medic_name' => $user['name'],
                    'medic_jabatan' => $user['position'],
                    'session_start' => now(),
                ]);
            }

            FarmasiActivity::create([
                'activity_type' => 'online',
                'medic_user_id' => $user['id'],
                'medic_name' => $user['name'],
                'description' => "Status diubah menjadi ONLINE",
            ]);
        }
        else {
            $mStatus->status = 'offline';
            $mStatus->save();

            // End active session
            $activeSession = UserFarmasiSession::where('user_id', $user['id'])
                ->whereNull('session_end')
                ->first();
            if ($activeSession) {
                $duration = now()->diffInSeconds($activeSession->session_start);
                $activeSession->update([
                    'session_end' => now(),
                    'duration_seconds' => $duration,
                    'end_reason' => 'manual_offline',
                    'ended_by_user_id' => $user['id'],
                ]);
            }

            FarmasiActivity::create([
                'activity_type' => 'offline',
                'medic_user_id' => $user['id'],
                'medic_name' => $user['name'],
                'description' => "Status diubah menjadi OFFLINE",
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Force Offline
     */
    public function forceOffline(Request $request)
    {
        $user = session('user_rh');
        if (strtolower($user['role']) === 'staff') {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $targetId = $request->target_user_id;
        $reason = $request->reason;

        $mStatus = UserFarmasiStatus::where('user_id', $targetId)->first();
        if ($mStatus) {
            $mStatus->update(['status' => 'offline']);

            // End active session
            $activeSession = UserFarmasiSession::where('user_id', $targetId)
                ->whereNull('session_end')
                ->first();
            if ($activeSession) {
                $duration = now()->diffInSeconds($activeSession->session_start);
                $activeSession->update([
                    'session_end' => now(),
                    'duration_seconds' => $duration,
                    'end_reason' => 'force_offline',
                    'ended_by_user_id' => $user['id'],
                ]);
            }

            $targetUser = UserRh::find($targetId);
            FarmasiActivity::create([
                'activity_type' => 'force_offline',
                'medic_user_id' => $user['id'],
                'medic_name' => $user['name'],
                'description' => "🛑 FORCE OFFLINE: " . ($targetUser->full_name ?? $targetId) . " | Alasan: $reason",
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Get Status
     * Mirror: legacy/actions/get_farmasi_status.php
     */
    public function getStatus()
    {
        $user = session('user_rh');
        if (!$user) return response()->json(['status' => 'offline']);

        $status = DB::table('user_farmasi_status')
            ->where('user_id', $user['id'])
            ->value('status') ?: 'offline';

        return response()->json(['status' => $status]);
    }

    /**
     * AJAX: Get Global Cooldown
     * Mirror: legacy/actions/get_global_cooldown.php
     */
    public function getGlobalCooldown()
    {
        $user = session('user_rh');
        $userId = (int)($user['id'] ?? 0);

        $currentHour = (int)now('Asia/Jakarta')->format('H');
        $isAfternoonPeak = ($currentHour >= 15 && $currentHour < 18);
        $isNightPeak = ($currentHour >= 21 || $currentHour < 3);

        if ($isAfternoonPeak || $isNightPeak) {
            return response()->json(['active' => false, 'reason' => 'peak_hours']);
        }

        if ($userId <= 0) return response()->json(['active' => false]);

        $row = DB::table('sales')
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$row) return response()->json(['active' => false]);

        $COOLDOWN_SECONDS = 60;
        $lastTime = Carbon::parse($row->created_at)->timestamp;
        $remain = $COOLDOWN_SECONDS - (time() - $lastTime);

        if ($remain <= 0 || (int)$row->medic_user_id !== $userId) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'remain' => $remain,
            'last_by' => $row->medic_name
        ]);
    }

    /**
     * AJAX: Get Fairness Status
     * Mirror: legacy/actions/get_fairness_status.php
     */
    public function getFairnessStatus()
    {
        $user = session('user_rh');
        $userId = (int)($user['id'] ?? 0);
        $THRESHOLD = 10;

        if ($userId <= 0) {
            return response()->json(['blocked' => false, 'selisih' => 0, 'threshold' => $THRESHOLD, 'user_status' => 'offline']);
        }

        $selfStatus = DB::table('user_farmasi_status')->where('user_id', $userId)->value('status') ?: 'offline';

        $medics = DB::table('user_farmasi_status as ufs')
            ->join('user_rh as ur', 'ur.id', '=', 'ufs.user_id')
            ->leftJoin('sales as s', function($join) {
                $join->on('s.medic_user_id', '=', 'ufs.user_id')
                     ->whereDate('s.created_at', Carbon::today());
            })
            ->select('ufs.user_id', 'ufs.status', 'ur.full_name as medic_name', 'ur.position as medic_jabatan', DB::raw('COUNT(s.id) as total_transaksi'))
            ->groupBy('ufs.user_id', 'ufs.status', 'ur.full_name', 'ur.position')
            ->get();

        $onlineRows = $medics->filter(fn($m) => $m->status === 'online')->values();

        if ($onlineRows->count() < 2) {
            return response()->json(['blocked' => false, 'selisih' => 0, 'threshold' => $THRESHOLD, 'user_status' => $selfStatus]);
        }

        $sortedOnline = $onlineRows->sortBy('total_transaksi')->values();
        $lowestOnline = $sortedOnline[0];
        $current = $onlineRows->firstWhere('user_id', $userId);

        if (!$current) {
            return response()->json(['blocked' => false, 'selisih' => 0, 'threshold' => $THRESHOLD, 'user_status' => $selfStatus]);
        }

        $diff = (int)$current->total_transaksi - (int)$lowestOnline->total_transaksi;

        $response = [
            'blocked' => false,
            'selisih' => max(0, $diff),
            'threshold' => $THRESHOLD,
            'user_status' => $selfStatus
        ];

        if ($diff >= $THRESHOLD && (int)$lowestOnline->user_id !== $userId) {
            $response['blocked'] = true;
            $response['medic_name'] = $lowestOnline->medic_name;
            $response['medic_jabatan'] = $lowestOnline->medic_jabatan;
            $response['total_transaksi'] = (int)$lowestOnline->total_transaksi;
        }

        return response()->json($response);
    }

    /**
     * AJAX: Koreksi Nama Konsumen
     * Mirror: legacy/actions/koreksi_nama_konsumen.php
     */
    public function koreksiNamaKonsumen(Request $request)
    {
        $user = session('user_rh');
        if (strtolower($user['role'] ?? '') === 'staff') {
            return response('Tidak punya izin koreksi', 403);
        }

        $oldName = trim($request->old_name);
        $newName = trim($request->new_name);

        if (!$oldName || !$newName) return response('Nama tidak valid', 400);
        if (strcasecmp($oldName, $newName) === 0) return response('Nama sama, tidak perlu dikoreksi');

        DB::beginTransaction();
        try {
            $affected = Sale::where('consumer_name', $oldName)->update(['consumer_name' => $newName]);
            
            DB::table('audit_logs')->insert([
                'action' => 'KOREKSI_NAMA_KONSUMEN',
                'detail' => "Dari '$oldName' -> '$newName' ($affected baris)",
                'created_at' => now()
            ]);

            DB::commit();
            return response("OK|$affected");
        } catch (\Exception $e) {
            DB::rollBack();
            return response("ERROR|" . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk Delete
     */
    public function destroyBulk(Request $request)
    {
        $user = session('user_rh');
        $ids = $request->sale_ids;
        if (empty($ids)) {
            return redirect()->back()->with('flash_errors', ['Tidak ada transaksi yang dipilih.']);
        }

        // Only delete own records
        $deleted = Sale::whereIn('id', $ids)
            ->where('medic_user_id', $user['id'])
            ->delete();

        if ($deleted > 0) {
            FarmasiActivity::create([
                'activity_type' => 'delete',
                'medic_user_id' => $user['id'],
                'medic_name' => $user['name'],
                'description' => "Menghapus $deleted transaksi",
            ]);
            return redirect()->back()->with('flash_messages', ["$deleted transaksi berhasil dihapus."]);
        }

        return redirect()->back()->with('flash_errors', ['Gagal menghapus transaksi.']);
    }

    /**
     * Rekap Farmasi V2 - Advanced Version
     * Mirror dari: legacy/dashboard/rekap_farmasi_v2.php
     * Fitur tambahan: Identity OCR, Auto-merge, Advanced validation
     */
    public function indexV2(Request $request)
    {
        $user = session('user_rh');
        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expired');
        }

        $medicName = $user['name'];
        $medicJabatan = $user['position'];
        $medicRole = $user['role'];

        // Access check: Trainee not allowed
        if (strtolower(trim($medicJabatan)) === 'trainee') {
            return response()->view('errors.403_farmasi', [], 403);
        }

        // Set default range = today untuk V2
        $range = $request->get('range', 'today');
        $startDate = $request->get('from', '');
        $endDate = $request->get('to', '');

        // Get date range data
        $dateRangeData = getDateRangeData();
        $rangeStart = $dateRangeData['rangeStart'];
        $rangeEnd = $dateRangeData['rangeEnd'];
        $rangeLabel = $dateRangeData['rangeLabel'];
        $weeks = $dateRangeData['weeks'] ?? [];

        // Packages - kelompokkan
        $packages = Package::orderBy('name')->get();
        $packagesById = [];

        $paketAB = [];
        $bandagePackages = [];
        $ifaksPackages = [];
        $painkillerPackages = [];

        foreach ($packages as $p) {
            $packagesById[$p->id] = [
                'name' => $p->name,
                'price' => (int)$p->price,
                'bandage' => (int)$p->bandage_qty,
                'ifaks' => (int)$p->ifaks_qty,
                'painkiller' => (int)$p->painkiller_qty,
            ];

            $name = strtoupper($p->name);
            if (str_starts_with($name, 'PAKET A') || str_starts_with($name, 'PAKET B')) {
                $paketAB[] = $p;
            } elseif ($p->bandage_qty > 0 && $p->ifaks_qty == 0 && $p->painkiller_qty == 0) {
                $bandagePackages[] = $p;
            } elseif ($p->ifaks_qty > 0 && $p->bandage_qty == 0 && $p->painkiller_qty == 0) {
                $ifaksPackages[] = $p;
            } elseif ($p->painkiller_qty > 0 && $p->bandage_qty == 0 && $p->ifaks_qty == 0) {
                $painkillerPackages[] = $p;
            }
        }

        // Hitung harga per pcs
        $pricePerPcs = [
            'bandage' => 0,
            'ifaks' => 0,
            'painkiller' => 0,
        ];

        foreach ($packages as $p) {
            if ($p->bandage_qty > 0 && $p->ifaks_qty == 0 && $p->painkiller_qty == 0) {
                $pricePerPcs['bandage'] = (int)($p->price / max(1, $p->bandage_qty));
            }
            if ($p->ifaks_qty > 0 && $p->bandage_qty == 0 && $p->painkiller_qty == 0) {
                $pricePerPcs['ifaks'] = (int)($p->price / max(1, $p->ifaks_qty));
            }
            if ($p->painkiller_qty > 0 && $p->bandage_qty == 0 && $p->ifaks_qty == 0) {
                $pricePerPcs['painkiller'] = (int)($p->price / max(1, $p->painkiller_qty));
            }
        }

        // Consumer names untuk datalist
        $consumerNames = Sale::distinct()->orderBy('consumer_name')->pluck('consumer_name');

        // Daily totals per konsumen untuk JavaScript
        $todayDate = date('Y-m-d');
        $dailyTotalsRows = Sale::selectRaw('consumer_name, COALESCE(SUM(qty_bandage),0) AS total_bandage, COALESCE(SUM(qty_ifaks),0) AS total_ifaks, COALESCE(SUM(qty_painkiller),0) AS total_painkiller')
            ->whereDate('created_at', $todayDate)
            ->groupBy('consumer_name')
            ->get();

        $dailyTotalsJS = [];
        foreach ($dailyTotalsRows as $row) {
            $key = mb_strtolower(trim($row->consumer_name));
            $dailyTotalsJS[$key] = [
                'bandage' => (int)$row->total_bandage,
                'ifaks' => (int)$row->total_ifaks,
                'painkiller' => (int)$row->total_painkiller,
            ];
        }

        // Daily detail untuk JavaScript
        $detailRows = Sale::select('consumer_name', 'medic_name', 'package_name', 'created_at', 'qty_bandage', 'qty_ifaks', 'qty_painkiller')
            ->whereDate('created_at', $todayDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $dailyDetailJS = [];
        foreach ($detailRows as $row) {
            $key = mb_strtolower(trim($row->consumer_name));
            if (!isset($dailyDetailJS[$key])) {
                $dailyDetailJS[$key] = [];
            }
            $dailyDetailJS[$key][] = [
                'medic' => $row->medic_name,
                'package' => $row->package_name,
                'time' => formatTanggalID($row->created_at),
                'bandage' => (int)$row->qty_bandage,
                'ifaks' => (int)$row->qty_ifaks,
                'painkiller' => (int)$row->qty_painkiller,
            ];
        }

        // Filtered sales
        $showAll = $request->get('show_all') === '1';
        $sqlSales = Sale::select('sales.*', 'identity_master.citizen_id')
            ->leftJoin('identity_master', 'identity_master.id', '=', 'sales.identity_id')
            ->whereBetween('sales.created_at', [$rangeStart, $rangeEnd]);

        if (!$showAll && $medicName) {
            $sqlSales->where('sales.medic_name', $medicName);
        }

        $filteredSales = $sqlSales->orderBy('sales.created_at', 'desc')->get();

        // Single medic stats untuk V2
        $singleMedicStats = null;
        if ($medicName) {
            $singleMedicStats = Sale::whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->where('medic_name', $medicName)
                ->selectRaw('medic_name, medic_jabatan, COUNT(*) as total_transaksi, SUM(qty_bandage + qty_ifaks + qty_painkiller) as total_item, SUM(price) as total_harga')
                ->groupBy('medic_name', 'medic_jabatan')
                ->first();
        }

        // Token untuk idempotency
        if (empty(session('tx_token'))) {
            session(['tx_token' => bin2hex(random_bytes(32))]);
        }

        // Clear form flag
        $shouldClearForm = session('clear_form', false);
        session()->forget('clear_form');

        // Flash messages
        $flashMessages = session('flash_messages', []);
        $flashWarnings = session('flash_warnings', []);
        $flashErrors = session('flash_errors', []);
        session()->forget(['flash_messages', 'flash_warnings', 'flash_errors']);

        return view('dashboard.rekap_farmasi_v2', [
            'pageTitle' => 'Rekap Farmasi EMS V2',
            'medicName' => $medicName,
            'medicJabatan' => $medicJabatan,
            'medicRole' => $medicRole,
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'weeks' => $weeks,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'fromDateInput' => ($range === 'custom') ? $startDate : '',
            'toDateInput' => ($range === 'custom') ? $endDate : '',
            'paketAB' => $paketAB,
            'bandagePackages' => $bandagePackages,
            'ifaksPackages' => $ifaksPackages,
            'painkillerPackages' => $painkillerPackages,
            'packagesById' => $packagesById,
            'pricePerPcs' => $pricePerPcs,
            'consumerNames' => $consumerNames,
            'dailyTotalsJS' => $dailyTotalsJS,
            'dailyDetailJS' => $dailyDetailJS,
            'filteredSales' => $filteredSales,
            'showAll' => $showAll,
            'singleMedicStats' => $singleMedicStats,
            'txToken' => session('tx_token'),
            'shouldClearForm' => $shouldClearForm,
            'flashMessages' => $flashMessages,
            'flashWarnings' => $flashWarnings,
            'flashErrors' => $flashErrors,
        ]);
    }

    /**
     * Store Transaction V2 (with auto-merge & identity support)
     */
    public function storeV2(Request $request)
    {
        $user = session('user_rh');
        $userId = $user['id'];
        $medicName = $user['name'];
        $medicJabatan = $user['position'];

        // Cooldown check
        $lastTx = session('last_tx_ts', 0);
        if (time() - $lastTx < 10) {
            return redirect()->back()->with('flash_errors', ['Mohon tunggu ' . (10 - (time() - $lastTx)) . ' detik sebelum input transaksi berikutnya.']);
        }

        // Status check
        $status = UserFarmasiStatus::where('user_id', $userId)->first();
        if (!$status || $status->status !== 'online') {
            return redirect()->back()->with('flash_errors', ['Anda berstatus OFFLINE.']);
        }

        $action = $request->action;
        $postedToken = $request->tx_token;

        // Idempotency check
        if (empty($postedToken) || empty(session('tx_token')) || !hash_equals(session('tx_token'), $postedToken)) {
            return redirect()->back()->with('flash_errors', ['Permintaan tidak valid atau sudah diproses.']);
        }

        session()->forget('tx_token');

        if ($action === 'add_sale') {
            $consumerName = ucwords(strtolower(trim($request->consumer_name)));
            $identityId = (int)($request->identity_id ?? 0);

            // Disable auto merge jika identity aktif
            $autoMerge = $request->has('auto_merge') && $request->auto_merge === '1' && $identityId === 0;

            $pkgMainId = (int)($request->package_main ?? 0);
            $pkgBandageId = (int)($request->package_bandage ?? 0);
            $pkgIfaksId = (int)($request->package_ifaks ?? 0);
            $pkgPainId = (int)($request->package_painkiller ?? 0);

            $forceOverLimit = $request->has('force_overlimit') && $request->force_overlimit === '1';

            if (empty($consumerName)) {
                return redirect()->back()->with('flash_errors', ['Identitas konsumen wajib diisi.']);
            }

            // Kumpulkan paket
            $selectedIds = [];
            if ($pkgMainId > 0) $selectedIds[] = $pkgMainId;
            if ($pkgBandageId > 0) $selectedIds[] = $pkgBandageId;
            if ($pkgIfaksId > 0) $selectedIds[] = $pkgIfaksId;
            if ($pkgPainId > 0) $selectedIds[] = $pkgPainId;

            if (empty($selectedIds)) {
                return redirect()->back()->with('flash_errors', ['Pilih minimal satu paket.']);
            }

            // Ambil detail paket
            $packages = Package::whereIn('id', $selectedIds)->get()->keyBy('id');

            if ($packages->count() !== count($selectedIds)) {
                return redirect()->back()->with('flash_errors', ['Ada paket yang tidak ditemukan.']);
            }

            // Hitung item baru
            $addBandage = 0;
            $addIfaks = 0;
            $addPain = 0;

            foreach ($selectedIds as $id) {
                $p = $packages[$id];
                $addBandage += (int)$p->bandage_qty;
                $addIfaks += (int)$p->ifaks_qty;
                $addPain += (int)$p->painkiller_qty;
            }

            // Validasi 1 identitas = 1 transaksi / hari
            $totalsToday = Sale::where('identity_id', $identityId)
                ->whereDate('created', date('Y-m-d'))
                ->selectRaw('COALESCE(SUM(qty_bandage),0) AS total_bandage, COALESCE(SUM(qty_ifaks),0) AS total_ifaks, COALESCE(SUM(qty_painkiller),0) AS total_painkiller')
                ->first();

            $totalsToday = $totalsToday ?: (object)['total_bandage' => 0, 'total_ifaks' => 0, 'total_painkiller' => 0];

            $totalTodayItem = (int)$totalsToday->total_bandage + (int)$totalsToday->total_ifaks + (int)$totalsToday->total_painkiller;

            if ($totalTodayItem > 0) {
                return redirect()->back()->with('flash_errors', ['Konsumen ini sudah melakukan transaksi hari ini (berdasarkan identitas).']);
            }

            $newBandage = $totalsToday->total_bandage + $addBandage;
            $newIfaks = $totalsToday->total_ifaks + $addIfaks;
            $newPain = $totalsToday->total_painkiller + $addPain;

            // Batas harian
            $maxBandage = 30;
            $maxIfaks = 10;
            $maxPain = 10;

            $overLimit = false;
            $warnings = [];

            if ($newBandage > $maxBandage) {
                $warnings[] = "⚠️ {$consumerName} melebihi batas BANDAGE ({$newBandage}/{$maxBandage}).";
                $overLimit = true;
            }
            if ($newIfaks > $maxIfaks) {
                $warnings[] = "⚠️ {$consumerName} melebihi batas IFAKS ({$newIfaks}/{$maxIfaks}).";
                $overLimit = true;
            }
            if ($newPain > $maxPain) {
                $warnings[] = "⚠️ {$consumerName} melebihi batas PAINKILLER ({$newPain}/{$maxPain}).";
                $overLimit = true;
            }

            if ($overLimit && !$forceOverLimit) {
                return redirect()->back()->with('flash_warnings', $warnings)
                    ->with('flash_errors', ['Transaksi dibatalkan karena melebihi batas harian.']);
            }

            // Auto merge konsumen
            if ($autoMerge && !empty($request->merge_targets)) {
                $decoded = json_decode($request->merge_targets, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $merged = 0;
                    foreach ($decoded as $oldName) {
                        if (!is_string($oldName)) continue;
                        $oldName = trim($oldName);
                        if ($oldName === '' || strcasecmp($oldName, $consumerName) === 0) continue;

                        $merged += Sale::where('consumer_name', $oldName)
                            ->update(['consumer_name' => $consumerName]);
                    }

                    if ($merged > 0) {
                        $warnings[] = "🔁 {$merged} transaksi lama digabung ke {$consumerName}.";
                    }
                }
            }

            // Insert transaksi
            DB::transaction(function () use ($request, $user, $consumerName, $packages, $postedToken, $identityId) {
                $now = now();

                foreach ($packages as $p) {
                    $txHash = hash('sha256', $postedToken . '|' . $p->id);
                    Sale::create([
                        'identity_id' => $identityId,
                        'consumer_name' => $consumerName,
                        'medic_name' => $user['name'],
                        'medic_user_id' => $user['id'],
                        'medic_jabatan' => $user['position'],
                        'package_id' => $p->id,
                        'package_name' => $p->name,
                        'price' => $p->price,
                        'qty_bandage' => $p->bandage_qty,
                        'qty_ifaks' => $p->ifaks_qty,
                        'qty_painkiller' => $p->painkiller_qty,
                        'created_at' => $now,
                        'tx_hash' => $txHash,
                    ]);
                }

                // Log activity
                $totalB = $packages->sum('bandage_qty');
                $totalI = $packages->sum('ifaks_qty');
                $totalP = $packages->sum('painkiller_qty');
                $totalPrice = $packages->sum('price');

                $items = [];
                if ($totalB > 0) $items[] = "$totalB Bandage";
                if ($totalI > 0) $items[] = "$totalI IFAKS";
                if ($totalP > 0) $items[] = "$totalP Painkiller";

                FarmasiActivity::create([
                    'activity_type' => 'transaction',
                    'medic_user_id' => $user['id'],
                    'medic_name' => $user['name'],
                    'description' => "Transaksi: $consumerName - " . implode(', ', $items) . " ($ " . number_format($totalPrice) . ")",
                ]);

                // Update status
                UserFarmasiStatus::updateOrCreate(
                    ['user_id' => $user['id']],
                    [
                        'status' => 'online',
                        'last_activity_at' => $now,
                        'last_confirm_at' => $now
                    ]
                );
            });

            session(['last_tx_ts' => time()]);
            session(['clear_form' => true]);

            $flashMessages = ["Transaksi {$consumerName} berhasil disimpan (" . count($packages) . " paket)."];
            if (!empty($warnings)) {
                return redirect()->back()->with('flash_messages', $flashMessages)
                    ->with('flash_warnings', $warnings);
            }

            return redirect()->back()->with('flash_messages', $flashMessages);
        }

        return redirect()->back();
    }

    /**
     * Bulk Delete V2
     */
    public function destroyBulkV2(Request $request)
    {
        $user = session('user_rh');
        $action = $request->action;

        if ($action === 'delete_selected') {
            $ids = $request->sale_ids ?? [];

            if (empty($ids) || !is_array($ids)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada transaksi yang dipilih.']);
            }

            // Clean IDs
            $cleanIds = [];
            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id > 0) $cleanIds[] = $id;
            }

            if (empty($cleanIds)) {
                return response()->json(['success' => false, 'message' => 'ID tidak valid.']);
            }

            // Only delete own records
            $deleted = Sale::whereIn('id', $cleanIds)
                ->where('medic_name', $user['name'])
                ->delete();

            if ($deleted > 0) {
                FarmasiActivity::create([
                    'activity_type' => 'delete',
                    'medic_user_id' => $user['id'],
                    'medic_name' => $user['name'],
                    'description' => "Menghapus $deleted transaksi",
                ]);
                return response()->json(['success' => true, 'deleted' => $deleted]);
            }

            return response()->json(['success' => false, 'message' => 'Gagal menghapus transaksi.']);
        }

        return response()->json(['success' => false, 'message' => 'Action tidak dikenali.']);
    }
}
