<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicOperasiPlastik;
use App\Models\UserRh;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OperasiPlastikController extends Controller
{
    /**
     * Operasi Plastik Page
     * Mirror of: legacy/dashboard/operasi_plastik.php
     */
    public function index()
    {
        $sessionUser = session('user_rh');
        $userId = (int)($sessionUser['id'] ?? 0);

        if ($userId <= 0) {
            return redirect()->route('login');
        }

        // Selected data for history
        $riwayat = MedicOperasiPlastik::with(['user', 'penanggungJawab', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 25-day limit check
        $lastOp = MedicOperasiPlastik::where('id_user', $userId)
            ->orderBy('tanggal', 'desc')
            ->first();

        $sisaHari = 0;
        $bolehInput = true;

        if ($lastOp && $lastOp->tanggal) {
            $lastDate = Carbon::parse($lastOp->tanggal);
            $diffHari = Carbon::today()->diffInDays($lastDate);

            if ($diffHari < 25) {
                $bolehInput = false;
                $sisaHari = 25 - $diffHari;
            }
        }

        // Penanggung Jawab list (Co.Ast and above)
        $penanggungJawab = UserRh::whereIn('position', ['(Co.Ast)', 'Dokter Umum', 'Dokter Spesialis'])
            ->orderBy('full_name', 'asc')
            ->get();

        return view('dashboard.operasi_plastik', [
            'pageTitle' => 'Operasi Plastik',
            'medicName' => $sessionUser['name'] ?? '',
            'medicPos' => $sessionUser['position'] ?? '',
            'userRole' => $sessionUser['role'] ?? 'Staff',
            'riwayat' => $riwayat,
            'bolehInput' => $bolehInput,
            'sisaHari' => $sisaHari,
            'penanggungJawab' => $penanggungJawab
        ]);
    }

    /**
     * Handle Store and Approval Actions
     * Mirror of: legacy/dashboard/operasi_plastik_action.php
     */
    public function handleAction(Request $request)
    {
        $sessionUser = session('user_rh');
        $userId = (int)($sessionUser['id'] ?? 0);
        $action = $request->input('action');

        // Approval / Rejection
        if (in_array($action, ['approve', 'reject'])) {
            if (strtolower($sessionUser['role'] ?? '') === 'staff') {
                return redirect()->back()->with('flash_errors', ['Anda tidak memiliki akses.']);
            }

            $op = MedicOperasiPlastik::findOrFail($request->id);
            if ($op->status !== 'pending') {
                return redirect()->back()->with('flash_errors', ['Data ini sudah diproses.']);
            }

            if ($action === 'approve') {
                $op->approve($userId);
                $msg = 'Operasi plastik berhasil di-approve.';
            }
            else {
                $op->reject($userId);
                $msg = 'Operasi plastik berhasil di-reject.';
            }

            return redirect()->back()->with('flash_messages', [$msg]);
        }

        // Store new request
        $request->validate([
            'tanggal' => 'required|date',
            'jenis_operasi' => 'required|in:Rekonstruksi Wajah,Suntik Putih',
            'alasan' => 'required|string',
            'id_penanggung_jawab' => 'required|integer',
        ]);

        // Re-check 25-day limit
        $lastOp = MedicOperasiPlastik::where('id_user', $userId)
            ->orderBy('tanggal', 'desc')
            ->first();
        if ($lastOp && $lastOp->tanggal) {
            $diffHari = Carbon::today()->diffInDays(Carbon::parse($lastOp->tanggal));
            if ($diffHari < 25) {
                return redirect()->back()->with('flash_errors', ['Operasi plastik hanya bisa dilakukan 1 bulan sekali. Tunggu ' . (25 - $diffHari) . ' hari lagi.']);
            }
        }

        DB::beginTransaction();
        try {
            $op = MedicOperasiPlastik::create([
                'id_user' => $userId,
                'tanggal' => $request->tanggal,
                'jenis_operasi' => $request->jenis_operasi,
                'alasan' => $request->alasan,
                'id_penanggung_jawab' => $request->id_penanggung_jawab,
                'status' => 'pending'
            ]);

            // Inbox notification
            DB::table('user_inbox')->insert([
                'user_id' => $request->id_penanggung_jawab,
                'title' => '🩺 Permohonan Operasi Plastik',
                'message' => "<b>Pengaju:</b> {$sessionUser['name']}<br><b>Jenis Operasi:</b> {$request->jenis_operasi}<br><b>Tanggal:</b> " . Carbon::parse($request->tanggal)->translatedFormat('d F Y') . "<br><b>Alasan:</b><br>" . nl2br(e($request->alasan)),
                'type' => 'operasi',
                'is_read' => 0,
                'created_at' => now()
            ]);

            DB::commit();

            // Note: push notification logic usually triggered here if needed.

            return redirect()->back()->with('flash_messages', [
                'Data operasi plastik berhasil disimpan.',
                'Permohonan telah dikirim ke penanggung jawab untuk ditinjau.'
            ]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('flash_errors', ['Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }
}
