<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\UserRh;

class ManageUserController extends Controller
{
    /**
     * Manajemen User Page
     * Mirror of: legacy/dashboard/manage_users.php
     */
    public function index()
    {
        $sessionUser = session('user_rh');
        $role = $sessionUser['role'] ?? '';

        if ($role === 'Staff') {
            return redirect()->route('dashboard.setting_akun');
        }

        $users = DB::table('user_rh as u')
            ->select(
            'u.id',
            'u.full_name',
            'u.position',
            'u.role',
            'u.is_active',
            'u.tanggal_masuk',
            'u.batch',
            'u.kode_nomor_induk_rs',
            'u.file_ktp',
            'u.file_sim',
            'u.file_kta',
            'u.file_skb',
            'u.sertifikat_heli',
            'u.resign_reason',
            'u.resigned_at',
            'r.full_name as resigned_by_name',
            'u.reactivated_at',
            'u.reactivated_note',
            'ra.full_name as reactivated_by_name'
        )
            ->leftJoin('user_rh as r', 'r.id', '=', 'u.resigned_by')
            ->leftJoin('user_rh as ra', 'ra.id', '=', 'u.reactivated_by')
            ->orderBy('u.is_active', 'desc')
            ->orderBy('u.full_name', 'asc')
            ->get();

        $usersByBatch = [];
        foreach ($users as $u) {
            $batchKey = !empty($u->batch) ? 'Batch ' . (int)$u->batch : 'Tanpa Batch';
            $usersByBatch[$batchKey][] = $u;
        }

        uksort($usersByBatch, function ($a, $b) {
            if ($a === 'Tanpa Batch')
                return 1;
            if ($b === 'Tanpa Batch')
                return -1;
            preg_match('/\d+/', $a, $ma);
            preg_match('/\d+/', $b, $mb);
            return ((int)($ma[0] ?? 0)) <=> ((int)($mb[0] ?? 0));
        });

        return view('dashboard.manage_users', [
            'pageTitle' => 'Manajemen User',
            'medicName' => $sessionUser['name'] ?? 'User',
            'medicPos' => $sessionUser['position'] ?? '-',
            'userRole' => $role,
            'usersByBatch' => $usersByBatch
        ]);
    }

    /**
     * Add New User
     * Mirror of: legacy/dashboard/manage_users_action.php (add_user)
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string',
            'position' => 'required|string',
            'role' => 'required|string',
        ]);

        $defaultPin = '0000';
        $user = UserRh::create([
            'full_name' => $request->full_name,
            'position' => $request->position,
            'role' => $request->role,
            'pin' => Hash::make($defaultPin),
            'batch' => $request->batch > 0 ? (int)$request->batch : null,
            'is_active' => 1,
            'is_verified' => 1
        ]);

        if ($user->batch > 0) {
            try {
                $user->kode_nomor_induk_rs = $this->generateKodeMedis($user->id, $user->full_name, $user->batch);
                $user->save();
            }
            catch (\Exception $e) {
                session()->flash('flash_warnings', ['User dibuat, tetapi kode medis gagal dibuat: ' . $e->getMessage()]);
            }
        }

        return redirect()->back()->with('flash_messages', ['User baru berhasil ditambahkan. PIN awal: 0000']);
    }

    /**
     * Update User
     * Mirror of: legacy/dashboard/manage_users_action.php (edit process)
     */
    public function update(Request $request)
    {
        $id = (int)$request->user_id;
        $user = UserRh::findOrFail($id);

        $user->full_name = $request->full_name;
        $user->position = $request->position;
        $user->role = $request->role;
        $user->batch = $request->batch > 0 ? (int)$request->batch : null;

        if (empty($user->kode_nomor_induk_rs) && $user->batch > 0) {
            $user->kode_nomor_induk_rs = $this->generateKodeMedis($user->id, $user->full_name, $user->batch);
        }

        if ($request->filled('new_pin')) {
            if (preg_match('/^\d{4}$/', $request->new_pin)) {
                $user->pin = Hash::make($request->new_pin);
            }
            else {
                return redirect()->back()->with('flash_errors', ['PIN harus 4 digit angka.']);
            }
        }

        $user->save();

        return redirect()->back()->with('flash_messages', ['Data user berhasil diperbarui.']);
    }

    /**
     * Delete User (Permanent)
     * Mirror of: legacy/dashboard/manage_users_action.php (delete)
     */
    public function destroy(Request $request)
    {
        $sessionUser = session('user_rh');
        if (!in_array($sessionUser['role'], ['Director', 'Vice Director'])) {
            return redirect()->back()->with('flash_errors', ['Hanya Director dan Vice Director yang dapat menghapus user.']);
        }

        $id = (int)$request->user_id;
        if ($id === (int)$sessionUser['id']) {
            return redirect()->back()->with('flash_errors', ['Anda tidak dapat menghapus akun sendiri.']);
        }

        UserRh::destroy($id);

        return redirect()->back()->with('flash_messages', ['User berhasil dihapus permanen.']);
    }

    /**
     * Resign User
     * Mirror of: legacy/dashboard/manage_users_action.php (resign)
     */
    public function resign(Request $request)
    {
        $id = (int)$request->user_id;
        $reason = $request->resign_reason;

        $user = UserRh::findOrFail($id);
        $user->is_active = 0;
        $user->resign_reason = $reason;
        $user->resigned_by = session('user_rh')['id'];
        $user->resigned_at = now();
        $user->save();

        return redirect()->back()->with('flash_messages', ['User berhasil dinonaktifkan.']);
    }

    /**
     * Reactivate User
     * Mirror of: legacy/dashboard/manage_users_action.php (reactivate)
     */
    public function reactivate(Request $request)
    {
        $id = (int)$request->user_id;
        $note = $request->reactivate_note;

        $user = UserRh::findOrFail($id);
        $user->is_active = 1;
        $user->reactivated_at = now();
        $user->reactivated_by = session('user_rh')['id'];
        $user->reactivated_note = $note;
        $user->save();

        return redirect()->back()->with('flash_messages', ['User berhasil diaktifkan kembali.']);
    }

    /**
     * Delete Kode Medis (Ajax)
     * Mirror of: legacy/dashboard/manage_users_action.php (delete_kode_medis)
     */
    public function deleteKodeMedis(Request $request)
    {
        $sessionRole = session('user_rh')['role'] ?? '';
        if (!in_array($sessionRole, ['Director', 'Vice Director'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak']);
        }

        $userId = (int)$request->user_id;
        $user = UserRh::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak valid']);
        }

        $user->kode_nomor_induk_rs = null;
        $user->save();

        return response()->json(['success' => true]);
    }

    /**
     * Helper: Generate Kode Medis
     */
    private function generateKodeMedis(int $userId, string $fullName, int $batch): string
    {
        if ($batch < 1 || $batch > 26) {
            throw new \Exception('Batch tidak valid');
        }

        $batchCode = chr(64 + $batch);
        $idPart = str_pad((string)$userId, 2, '0', STR_PAD_LEFT);
        $parts = preg_split('/\s+/', strtoupper(trim($fullName)));
        $firstName = $parts[0] ?? '';
        $lastName = $parts[count($parts) - 1] ?? '';
        $letters = substr($firstName, 0, 2) . substr($lastName, 0, 2);

        $numberPart = '';
        foreach (str_split($letters) as $char) {
            if ($char >= 'A' && $char <= 'Z') {
                $numberPart .= str_pad((string)(ord($char) - 64), 2, '0', STR_PAD_LEFT);
            }
        }

        return 'RH' . $batchCode . '-' . $idPart . $numberPart;
    }

    /**
     * Handle Manual Actions (POST)
     */
    public function handleAction(Request $request)
    {
        $action = $request->action;
        if ($action === 'add_user')
            return $this->store($request);
        if ($action === 'resign')
            return $this->resign($request);
        if ($action === 'reactivate')
            return $this->reactivate($request);
        if ($action === 'delete')
            return $this->destroy($request);
        if ($action === 'delete_kode_medis')
            return $this->deleteKodeMedis($request);

        return $this->update($request);
    }
}
