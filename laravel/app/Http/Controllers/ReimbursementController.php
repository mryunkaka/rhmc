<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Reimbursement Controller
 * Mirror dari: legacy/dashboard/reimbursement.php + action files
 */
class ReimbursementController extends Controller
{
    /**
     * Display reimbursement page
     * Mirror dari: legacy/dashboard/reimbursement.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Set default range = week3 jika tidak ada
        if (!$request->has('range')) {
            $request->merge(['range' => 'week3']);
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

        $isDirector = in_array($userRole, ['vice director', 'director'], true);
        $canPayReimbursement = $userRole !== 'staff';

        // Filter input
        $startDate = $request->get('from', '');
        $endDate = $request->get('to', '');

        // Build query
        $sql = "
            SELECT
                r.reimbursement_code,
                MAX(r.billing_source_type) AS billing_source_type,
                MAX(r.billing_source_name) AS billing_source_name,
                MAX(r.item_name) AS item_name,
                MAX(r.status) AS status,
                MIN(r.created_at) AS created_at,
                SUM(r.amount) AS total_amount,
                MAX(r.receipt_file) AS receipt_file,
                MAX(r.paid_at) AS paid_at,
                MAX(u.full_name) AS paid_by_name,
                MAX(r.created_by) AS created_by_id,
                MAX(cby.full_name) AS created_by_name
            FROM reimbursements r
            LEFT JOIN user_rh u ON u.id = r.paid_by
            LEFT JOIN user_rh cby ON cby.id = r.created_by
            WHERE 1=1
        ";

        $params = [];
        $range = $request->get('range', 'week3');

        if ($range !== 'custom') {
            $sql .= " AND DATE(r.created_at) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $rangeStart;
            $params[':end_date'] = $rangeEnd;
        } elseif ($startDate && $endDate) {
            $sql .= " AND DATE(r.created_at) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        $sql .= "
            GROUP BY reimbursement_code
            ORDER BY MIN(r.created_at) DESC
        ";

        $rows = DB::select($sql, $params);

        $pageTitle = 'Reimbursement';

        return view('reimbursement.index', compact(
            'pageTitle',
            'rangeLabel',
            'rows',
            'userRole',
            'isDirector',
            'canPayReimbursement'
        ));
    }

    /**
     * Store new reimbursement
     * Mirror dari: legacy/dashboard/reimbursement_action.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $userRh = session('user_rh');
        $userId = (int)($userRh['id'] ?? 0);

        if ($userId <= 0) {
            return redirect()->back()->with('error', 'Session tidak valid');
        }

        // Validate input
        $code = trim($request->input('reimbursement_code', ''));
        $type = trim($request->input('billing_source_type', ''));
        $name = trim($request->input('billing_source_name', ''));
        $item = trim($request->input('item_name', ''));
        $qty = (int)($request->input('qty', 0));
        $price = (int)($request->input('price', 0));

        if ($code === '' || $type === '' || $name === '' || $item === '' || $qty <= 0 || $price < 0) {
            return redirect()->back()->with('error', 'Data reimbursement tidak lengkap.');
        }

        $amount = $qty * $price;
        $receiptPath = null;

        // Handle file upload
        if ($request->hasFile('receipt_file') && $request->file('receipt_file')->isValid()) {
            $file = $request->file('receipt_file');
            $info = getimagesize($file->getPathname());

            if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
                return redirect()->back()->with('error', 'Bukti pembayaran harus JPG atau PNG.');
            }

            // Create folder per reimbursement_code
            $folder = public_path('storage/reimbursements/' . $code);
            if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
                return redirect()->back()->with('error', 'Gagal membuat folder penyimpanan.');
            }

            $ext = $info['mime'] === 'image/png' ? 'png' : 'jpg';
            $target = $folder . '/receipt.' . $ext;

            // Compress and save image
            if (!$this->compressImageSmart($file->getPathname(), $target)) {
                return redirect()->back()->with('error', 'Gagal memproses bukti pembayaran.');
            }

            $receiptPath = 'storage/reimbursements/' . $code . '/receipt.' . $ext;
        }

        // Insert database
        DB::table('reimbursements')->insert([
            'reimbursement_code' => $code,
            'billing_source_type' => $type,
            'billing_source_name' => $name,
            'item_name' => $item,
            'qty' => $qty,
            'price' => $price,
            'amount' => $amount,
            'receipt_file' => $receiptPath,
            'status' => 'submitted',
            'created_by' => $userId,
            'submitted_at' => now(),
            'created_at' => now()
        ]);

        return redirect()->route('dashboard.reimbursement', ['range' => 'week4'])
            ->with('success', 'Reimbursement berhasil disimpan.');
    }

    /**
     * Pay reimbursement
     * Mirror dari: legacy/dashboard/reimbursement_pay.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function pay(Request $request)
    {
        $userRh = session('user_rh');
        $userId = (int)($userRh['id'] ?? 0);
        $role = strtolower(trim($userRh['role'] ?? ''));

        $allowedRoles = ['manager', 'director', 'vice director'];

        if ($userId <= 0 || !in_array($role, $allowedRoles, true)) {
            return response('Akses ditolak', 403);
        }

        $code = trim($request->input('code', ''));

        if ($code === '') {
            return response('Kode reimbursement tidak valid', 400);
        }

        // Update status
        DB::table('reimbursements')
            ->where('reimbursement_code', $code)
            ->update([
                'status' => 'paid',
                'paid_by' => $userId,
                'paid_at' => now()
            ]);

        return response('OK', 200);
    }

    /**
     * Delete reimbursement
     * Mirror dari: legacy/dashboard/reimbursement_delete.php
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $userRh = session('user_rh');
        $userRole = strtolower(trim($userRh['role'] ?? ''));
        $code = $request->input('code', '');

        if (!in_array($userRole, ['vice director', 'director'])) {
            return response('Akses ditolak', 403);
        }

        if (!$code) {
            return response('Kode tidak valid', 400);
        }

        DB::table('reimbursements')
            ->where('reimbursement_code', $code)
            ->delete();

        return response('OK', 200);
    }

    /**
     * Compress image smart (same as setting_akun_action)
     * Helper function
     */
    private function compressImageSmart(
        string $sourcePath,
        string $targetPath,
        int $maxWidth = 1200,
        int $targetSize = 300000,
        int $minQuality = 70
    ): bool {
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $mime = $info['mime'];

        if ($mime === 'image/jpeg') {
            $src = imagecreatefromjpeg($sourcePath);
        } elseif ($mime === 'image/png') {
            $src = imagecreatefrompng($sourcePath);
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

        if ($mime === 'image/png') {
            imagepng($dst, $targetPath, 7);
        } else {
            for ($q = 90; $q >= $minQuality; $q -= 5) {
                imagejpeg($dst, $targetPath, $q);
                if (filesize($targetPath) <= $targetSize) break;
            }
        }

        imagedestroy($dst);
        return true;
    }
}
