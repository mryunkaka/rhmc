<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    /**
     * Mendapatkan user dari session
     */
    private function getUser()
    {
        return session('user_rh');
    }

    /**
     * Cek notifikasi farmasi
     * POST /actions/check_farmasi_notif
     */
    public function checkFarmasiNotif()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        $user = $this->getUser();
        $userId = $user['id'] ?? 0;

        if (!$userId) {
            return response()->json([
                'has_notif' => false,
                'status' => 'offline'
            ]);
        }

        // Ambil status user
        $status = DB::table('user_farmasi_status')
            ->where('user_id', $userId)
            ->value('status') ?: 'offline';

        // Jika sudah offline → tidak boleh ada modal
        if ($status === 'offline') {
            return response()->json([
                'has_notif' => false,
                'status' => 'offline'
            ]);
        }

        // Cek notif check_online
        $notif = DB::table('user_farmasi_notifications')
            ->select('message')
            ->where('user_id', $userId)
            ->where('type', 'check_online')
            ->where('is_read', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'has_notif' => (bool)$notif,
            'message' => data_get($notif, 'message'),
            'status' => 'online'
        ]);
    }

    /**
     * Konfirmasi farmasi online
     * POST /actions/confirm_farmasi_online
     */
    public function confirmFarmasiOnline()
    {
        $user = $this->getUser();
        $userId = $user['id'] ?? 0;

        if (!$userId) {
            return response('Unauthorized', 401);
        }

        try {
            DB::beginTransaction();

            // Reset status & timer
            DB::table('user_farmasi_status')
                ->where('user_id', $userId)
                ->update([
                'status' => 'online',
                'last_confirm_at' => now(),
                'last_activity_at' => now(),
                'auto_offline_at' => null,
                'updated_at' => now()
            ]);

            // Hapus notif check_online
            DB::table('user_farmasi_notifications')
                ->where('user_id', $userId)
                ->where('type', 'check_online')
                ->update(['is_read' => 1]);

            DB::commit();
            return response('OK');
        }
        catch (\Throwable $e) {
            DB::rollBack();
            return response('Error', 500);
        }
    }

    /**
     * Set farmasi offline
     * POST /actions/set_farmasi_offline
     */
    public function setFarmasiOffline()
    {
        $user = $this->getUser();
        $userId = $user['id'] ?? 0;

        if (!$userId) {
            return response('Unauthorized', 401);
        }

        try {
            DB::beginTransaction();

            // Set OFFLINE
            DB::table('user_farmasi_status')
                ->where('user_id', $userId)
                ->update([
                'status' => 'offline',
                'auto_offline_at' => now()
            ]);

            // Hapus semua notif
            DB::table('user_farmasi_notifications')
                ->where('user_id', $userId)
                ->delete();

            DB::commit();
            return response('OK');
        }
        catch (\Throwable $e) {
            DB::rollBack();
            return response('Error', 500);
        }
    }

    /**
     * Get farmasi deadline
     * GET /actions/get_farmasi_deadline
     */
    public function getFarmasiDeadline()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $user = $this->getUser();
        $userId = $user['id'] ?? 0;

        if (!$userId) {
            return response()->json([
                'active' => false
            ]);
        }

        $row = DB::table('user_farmasi_status')
            ->select(
            'auto_offline_at',
            DB::raw('TIMESTAMPDIFF(SECOND, NOW(), auto_offline_at) AS remaining_seconds')
        )
            ->where('user_id', $userId)
            ->where('status', 'online')
            ->whereNotNull('auto_offline_at')
            ->first();

        $rem = data_get($row, 'remaining_seconds');
        if ($row && $rem && (int)$rem > 0) {
            return response()->json([
                'active' => true,
                'remaining' => (int)$rem
            ]);
        }

        return response()->json([
            'active' => false
        ]);
    }

    /**
     * Heartbeat - update activity tracker
     * POST /actions/heartbeat
     */
    public function heartbeat()
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user || empty($user['id'])) {
            return response()->json(['active' => false]);
        }

        $userId = (int)$user['id'];

        // Update HANYA jika status online
        $affected = DB::table('user_farmasi_status')
            ->where('user_id', $userId)
            ->where('status', 'online')
            ->update(['last_activity_at' => now()]);

        if ($affected === 0) {
            // User offline → heartbeat tidak aktif
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get inbox messages
     * GET /actions/get_inbox
     */
    public function getInbox()
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user) {
            return response()->json([
                'unread' => 0,
                'items' => []
            ]);
        }

        $userId = (int)$user['id'];

        $items = DB::table('user_inbox')
            ->select(
            'id',
            'title',
            'message',
            'is_read',
            'created_at',
            DB::raw("DATE_FORMAT(created_at, '%d %b %Y %H:%i WIB') AS created_at_label")
        )
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        $unread = $items->filter(function ($item) {
            return (int)($item->is_read ?? 0) === 0;
        })->count();

        return response()->json([
            'unread' => $unread,
            'items' => $items
        ]);
    }

    /**
     * Read inbox message
     * POST /actions/read_inbox
     */
    public function readInbox(Request $request)
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user || empty($request->id)) {
            return response()->json(['success' => false]);
        }

        $id = (int)$request->id;
        $userId = (int)$user['id'];

        DB::table('user_inbox')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_read' => 1]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete inbox message
     * POST /actions/delete_inbox
     */
    public function deleteInbox(Request $request)
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user || empty($request->id)) {
            return response()->json(['success' => false]);
        }

        $id = (int)$request->id;
        $userId = (int)$user['id'];

        DB::table('user_inbox')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Ping farmasi activity
     * POST /actions/ping_farmasi_activity
     */
    public function pingFarmasiActivity()
    {
        header('Content-Type: application/json');

        $user = $this->getUser();
        if (!$user || empty($user['id'])) {
            return response()->json(['active' => false]);
        }

        $userId = (int)$user['id'];

        // Update activity HANYA jika status online
        $affected = DB::table('user_farmasi_status')
            ->where('user_id', $userId)
            ->where('status', 'online')
            ->update(['last_activity_at' => now()]);

        return response()->json([
            'active' => $affected > 0,
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Search Medic for Autocomplete
     * Mirror of: legacy/actions/search_medic.php
     */
    public function searchMedic(Request $request)
    {
        header('Content-Type: application/json');

        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['success' => false, 'medics' => []]);
        }

        try {
            $medics = DB::table('user_rh')
                ->select('id', 'full_name', 'position')
                ->where('full_name', 'LIKE', "%$query%")
                ->where('is_active', 1)
                ->orderBy('full_name', 'asc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'medics' => $medics
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Search User RH (AJAX)
     * Mirror of: legacy/ajax/search_user_rh.php
     */
    public function searchUserRh(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $users = DB::table('user_rh')
            ->select('id', 'full_name', 'batch', 'position', 'jenis_kelamin')
            ->whereRaw('LOWER(full_name) LIKE LOWER(?)', ['%' . $q . '%'])
            ->where('is_active', 1)
            ->orderBy('full_name', 'asc')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    /**
     * Save web push subscription
     * POST /actions/save_push_subscription.php
     */
    public function savePushSubscription(Request $request)
    {
        $user = $this->getUser();
        $userId = $user['id'] ?? 0;

        if (!$userId) {
            return response('', 401);
        }

        $data = $request->json()->all();
        if (!$data || empty($data['endpoint'])) {
            return response('', 400);
        }

        DB::statement(
            "
            INSERT INTO user_push_subscriptions
            (user_id, endpoint, p256dh, auth, created_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                p256dh = VALUES(p256dh),
                auth   = VALUES(auth),
                updated_at = NOW()
            ",
            [
                $userId,
                $data['endpoint'],
                data_get($data, 'keys.p256dh'),
                data_get($data, 'keys.auth'),
            ]
        );

        return response('OK');
    }
}
