<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Restaurant Controller
 * Mirror dari: legacy/dashboard/restaurant_consumption.php + restaurant_settings.php
 */
class RestaurantController extends Controller
{
    /**
     * Display restaurant consumption page
     * Mirror dari: legacy/dashboard/restaurant_consumption.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Set default range = week4 jika tidak ada
        if (!$request->has('range')) {
            $request->merge(['range' => 'week4']);
        }

        // Get date range data
        $dateRangeData = getDateRangeData();
        $rangeStart = $dateRangeData['rangeStart'];
        $rangeEnd = $dateRangeData['rangeEnd'];
        $rangeLabel = $dateRangeData['rangeLabel'];

        // Get user data dari session
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));
        $userId = (int)($userRh['id'] ?? 0);
        $userName = $userRh['name'] ?? '';

        $isDirector = in_array($userRole, ['vice director', 'director'], true);
        $canManage = !in_array($userRole, ['staff', 'manager'], true);

        // Ambil data restoran settings
        $restaurants = DB::table('restaurant_settings')
            ->select('id', 'restaurant_name', 'price_per_packet', 'tax_percentage', 'is_active')
            ->where('is_active', 1)
            ->orderBy('restaurant_name')
            ->get();

        // Filter input
        $startDate = $request->get('from', '');
        $endDate = $request->get('to', '');

        // Build query
        $sql = "
            SELECT
                rc.*,
                u1.full_name AS created_by_name,
                u2.full_name AS approved_by_name,
                u3.full_name AS paid_by_name
            FROM restaurant_consumptions rc
            LEFT JOIN user_rh u1 ON u1.id = rc.created_by
            LEFT JOIN user_rh u2 ON u2.id = rc.approved_by
            LEFT JOIN user_rh u3 ON u3.id = rc.paid_by
            WHERE 1=1
        ";

        $params = [];
        $range = $request->get('range', 'week4');

        if ($range !== 'custom') {
            $sql .= " AND DATE(rc.delivery_date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $rangeStart;
            $params[':end_date'] = $rangeEnd;
        } elseif ($startDate && $endDate) {
            $sql .= " AND DATE(rc.delivery_date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        $sql .= " ORDER BY rc.delivery_date DESC, rc.created_at DESC";

        $rows = DB::select($sql, $params);

        // Hitung total statistik
        $sqlTotal = "
            SELECT
                COUNT(*) AS total_record,
                SUM(packet_count) AS total_packets,
                SUM(subtotal) AS total_subtotal,
                SUM(tax_amount) AS total_tax,
                SUM(total_amount) AS total_grand
            FROM restaurant_consumptions rc
            WHERE 1=1
        ";

        $paramsTotal = [];

        if ($range !== 'custom') {
            $sqlTotal .= " AND DATE(rc.delivery_date) BETWEEN :start_date AND :end_date";
            $paramsTotal[':start_date'] = $rangeStart;
            $paramsTotal[':end_date'] = $rangeEnd;
        } elseif ($startDate && $endDate) {
            $sqlTotal .= " AND DATE(rc.delivery_date) BETWEEN :start_date AND :end_date";
            $paramsTotal[':start_date'] = $startDate;
            $paramsTotal[':end_date'] = $endDate;
        }

        $stats = DB::select($sqlTotal, $paramsTotal);
        $stats = $stats[0] ?? (object)[
            'total_packets' => 0,
            'total_subtotal' => 0,
            'total_tax' => 0,
            'total_grand' => 0
        ];

        $pageTitle = 'Restaurant Consumption';

        return view('restaurant.consumption', compact(
            'pageTitle',
            'rangeLabel',
            'rows',
            'restaurants',
            'stats',
            'userRole',
            'userId',
            'userName',
            'isDirector',
            'canManage'
        ));
    }

    /**
     * Store new restaurant consumption
     * Mirror dari: legacy/dashboard/restaurant_consumption_action.php?action=create
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $userRh = session('user_rh');
        $userId = (int)($userRh['id'] ?? 0);
        $userName = $userRh['name'] ?? '';
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $restaurantId = (int)$request->input('restaurant_id', 0);
            $packetCount = (int)$request->input('packet_count', 0);
            $deliveryDate = $request->input('delivery_date', '');
            $deliveryTime = $request->input('delivery_time', '');
            $notes = $request->input('notes', '');

            // Ambil data restoran
            $resto = DB::table('restaurant_settings')
                ->select('restaurant_name', 'price_per_packet', 'tax_percentage')
                ->where('id', $restaurantId)
                ->where('is_active', 1)
                ->first();

            if (!$resto) {
                throw new \Exception('Restoran tidak ditemukan');
            }

            $pricePerPacket = (float)$resto->price_per_packet;
            $taxPercentage = (float)$resto->tax_percentage;

            // Kalkulasi
            $subtotal = $pricePerPacket * $packetCount;
            $taxAmount = $subtotal * ($taxPercentage / 100);
            $totalAmount = $subtotal + $taxAmount;

            // Handle file upload KTP with compression
            $ktpFile = null;
            if ($request->hasFile('ktp_file') && $request->file('ktp_file')->isValid()) {
                $file = $request->file('ktp_file');
                $uploadDir = public_path('storage/restaurant_ktp/');

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $tmpPath = $file->getPathname();
                $info = getimagesize($tmpPath);

                // Validasi tipe file
                if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
                    throw new \Exception('File KTP harus berupa JPG atau PNG');
                }

                // Semua file dikonversi ke JPEG untuk kompresi lebih baik
                $filename = 'ktp_' . time() . '_' . $userId . '.jpg';
                $finalPath = $uploadDir . $filename;

                // Kompres gambar
                if (!$this->compressImageSmart($tmpPath, $finalPath, 800, 300000, 50)) {
                    throw new \Exception('Gagal memproses file KTP');
                }

                $ktpFile = 'storage/restaurant_ktp/' . $filename;
            }

            // Insert data
            DB::table('restaurant_consumptions')->insert([
                'consumption_code' => $request->input('consumption_code'),
                'restaurant_id' => $restaurantId,
                'restaurant_name' => $resto->restaurant_name,
                'recipient_user_id' => $userId,
                'recipient_name' => $request->input('recipient_name', $userName),
                'delivery_date' => $deliveryDate,
                'delivery_time' => $deliveryTime,
                'packet_count' => $packetCount,
                'price_per_packet' => $pricePerPacket,
                'tax_percentage' => $taxPercentage,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'ktp_file' => $ktpFile,
                'notes' => $notes,
                'status' => 'pending',
                'created_by' => $userId,
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Konsumsi berhasil dicatat!'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Approve consumption
     * Mirror dari: legacy/dashboard/restaurant_consumption_action.php?action=approve
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request)
    {
        $userRh = session('user_rh');
        $userId = (int)($userRh['id'] ?? 0);
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Cek permission
        if (in_array($userRole, ['staff', 'manager'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menyetujui'
            ]);
        }

        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid'
            ]);
        }

        $affected = DB::table('restaurant_consumptions')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now()
            ]);

        if ($affected > 0) {
            return response()->json(['success' => true]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan atau sudah diproses'
            ]);
        }
    }

    /**
     * Mark as paid
     * Mirror dari: legacy/dashboard/restaurant_consumption_action.php?action=paid
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paid(Request $request)
    {
        $userRh = session('user_rh');
        $userId = (int)($userRh['id'] ?? 0);
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Cek permission
        if (in_array($userRole, ['staff', 'manager'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin'
            ]);
        }

        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid'
            ]);
        }

        $affected = DB::table('restaurant_consumptions')
            ->where('id', $id)
            ->where('status', 'approved')
            ->update([
                'status' => 'paid',
                'paid_by' => $userId,
                'paid_at' => now()
            ]);

        if ($affected > 0) {
            return response()->json(['success' => true]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan atau status tidak valid'
            ]);
        }
    }

    /**
     * Delete consumption (Director only)
     * Mirror dari: legacy/dashboard/restaurant_consumption_action.php?action=delete
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        if (!in_array($userRole, ['vice director', 'director'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Director yang bisa menghapus'
            ]);
        }

        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid'
            ]);
        }

        try {
            // Ambil info file untuk dihapus
            $row = DB::table('restaurant_consumptions')
                ->select('ktp_file')
                ->where('id', $id)
                ->first();

            if ($row && !empty($row->ktp_file)) {
                $filePath = public_path($row->ktp_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus dari database
            DB::table('restaurant_consumptions')
                ->where('id', $id)
                ->delete();

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display restaurant settings page
     * Mirror dari: legacy/dashboard/restaurant_settings.php
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Role check - Hanya selain staff/manager yang boleh akses
        if (in_array($userRole, ['staff', 'manager'], true)) {
            abort(403, 'Akses Ditolak');
        }

        // Ambil data restoran
        $restaurants = DB::table('restaurant_settings')
            ->orderBy('restaurant_name')
            ->get();

        $pageTitle = 'Restaurant Settings';

        return view('restaurant.settings', compact(
            'pageTitle',
            'restaurants'
        ));
    }

    /**
     * Store new restaurant setting
     * Mirror dari: legacy/dashboard/restaurant_settings_action.php?action=create
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeSettings(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Permission check
        if (in_array($userRole, ['staff', 'manager'], true)) {
            return redirect()->back()
                ->with('error', 'Anda tidak memiliki izin untuk melakukan aksi ini');
        }

        $restaurantName = trim($request->input('restaurant_name', ''));
        $pricePerPacket = (float)$request->input('price_per_packet', 0);
        $taxPercentage = (float)$request->input('tax_percentage', 5);
        $isActive = $request->has('is_active') ? 1 : 0;

        if (empty($restaurantName)) {
            return redirect()->back()
                ->with('error', 'Nama restoran wajib diisi');
        }

        if ($pricePerPacket < 0) {
            return redirect()->back()
                ->with('error', 'Harga tidak valid');
        }

        try {
            DB::table('restaurant_settings')->insert([
                'restaurant_name' => $restaurantName,
                'price_per_packet' => $pricePerPacket,
                'tax_percentage' => $taxPercentage,
                'is_active' => $isActive,
                'created_at' => now()
            ]);

            return redirect()->route('dashboard.restaurant.settings')
                ->with('success', 'Restoran berhasil ditambahkan!');

        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan restoran');
        }
    }

    /**
     * Update restaurant setting
     * Mirror dari: legacy/dashboard/restaurant_settings_action.php?action=update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Permission check
        if (in_array($userRole, ['staff', 'manager'], true)) {
            return redirect()->back()
                ->with('error', 'Anda tidak memiliki izin untuk melakukan aksi ini');
        }

        $id = (int)$request->input('id', 0);
        $restaurantName = trim($request->input('restaurant_name', ''));
        $pricePerPacket = (float)$request->input('price_per_packet', 0);
        $taxPercentage = (float)$request->input('tax_percentage', 0);
        $isActive = $request->has('is_active') ? 1 : 0;

        if ($id <= 0) {
            return redirect()->back()
                ->with('error', 'ID tidak valid');
        }

        if (empty($restaurantName)) {
            return redirect()->back()
                ->with('error', 'Nama restoran wajib diisi');
        }

        try {
            DB::table('restaurant_settings')
                ->where('id', $id)
                ->update([
                    'restaurant_name' => $restaurantName,
                    'price_per_packet' => $pricePerPacket,
                    'tax_percentage' => $taxPercentage,
                    'is_active' => $isActive,
                    'updated_at' => now()
                ]);

            return redirect()->route('dashboard.restaurant.settings')
                ->with('success', 'Restoran berhasil diperbarui!');

        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui restoran');
        }
    }

    /**
     * Toggle restaurant status
     * Mirror dari: legacy/dashboard/restaurant_settings_action.php?action=toggle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Permission check
        if (in_array($userRole, ['staff', 'manager'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin'
            ]);
        }

        $id = (int)$request->input('id', 0);
        $isActive = (int)$request->input('is_active', 0);

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid'
            ]);
        }

        try {
            DB::table('restaurant_settings')
                ->where('id', $id)
                ->update([
                    'is_active' => $isActive,
                    'updated_at' => now()
                ]);

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete restaurant setting
     * Mirror dari: legacy/dashboard/restaurant_settings_action.php?action=delete
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSettings(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));

        // Permission check
        if (in_array($userRole, ['staff', 'manager'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin'
            ]);
        }

        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID tidak valid'
            ]);
        }

        try {
            // Cek apakah ada data konsumsi yang terkait
            $count = DB::table('restaurant_consumptions')
                ->where('restaurant_id', $id)
                ->count();

            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus! Masih ada ' . $count . ' data konsumsi yang terkait.'
                ]);
            }

            // Hapus restoran
            DB::table('restaurant_settings')
                ->where('id', $id)
                ->delete();

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Compress image smart (target 300KB - aggressive)
     * Helper function
     */
    private function compressImageSmart(
        string $sourcePath,
        string $targetPath,
        int $maxWidth = 800,
        int $targetSize = 300000,
        int $minQuality = 50
    ): bool {
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $mime = $info['mime'];
        if ($mime === 'image/jpeg') {
            $src = imagecreatefromjpeg($sourcePath);
        } elseif ($mime === 'image/png') {
            $src = imagecreatefrompng($sourcePath);
            // Konversi PNG ke JPEG untuk kompresi lebih baik
            $mime = 'image/jpeg';
        } else {
            return false;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        if ($w > $maxWidth) {
            $ratio = $maxWidth / $w;
            $nw = $maxWidth;
            $nh = (int)($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
        } else {
            $dst = $src;
        }

        // Kompresi sebagai JPEG dengan loop sampai target tercapai
        for ($q = 90; $q >= $minQuality; $q -= 5) {
            imagejpeg($dst, $targetPath, $q);
            if (filesize($targetPath) <= $targetSize) break;
        }

        imagedestroy($dst);
        return true;
    }
}
