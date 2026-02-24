<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display setting akun page
     * GET /dashboard/settings/akun
     */
    public function akun()
    {
        // Get user from session (mirror legacy)
        $userSession = session('user_rh', []);
        $userId = (int)($userSession['id'] ?? 0);

        // Fetch user data from database (mirror exact query from legacy)
        $userDb = DB::table('user_rh')
            ->select(
                'full_name',
                'position',
                'batch',
                'kode_nomor_induk_rs',
                'tanggal_masuk',
                'citizen_id',
                'no_hp_ic',
                'jenis_kelamin',
                'file_ktp',
                'file_sim',
                'file_kta',
                'file_skb',
                'sertifikat_heli',
                'sertifikat_operasi',
                'dokumen_lainnya'
            )
            ->where('id', $userId)
            ->first();

        if (!$userDb) {
            abort(404, 'User not found');
        }

        // Extract variables (mirror legacy)
        $citizenId = $userDb->citizen_id ?? '';
        $jenisKelamin = $userDb->jenis_kelamin ?? '';
        $noHpIc = $userDb->no_hp_ic ?? '';

        $medicName = $userDb->full_name ?? '';
        $medicPos = $userDb->position ?? '';
        $medicBatch = $userDb->batch ?? '';
        $nomorInduk = $userDb->kode_nomor_induk_rs ?? '';
        $tanggalMasuk = $userDb->tanggal_masuk ?? '';

        // Batch locked logic (mirror legacy)
        $batchLocked = !empty($nomorInduk);
        $kodeBatch = $nomorInduk;

        // Get flash messages (mirror legacy)
        $messages = session()->get('flash_messages', []);
        $warnings = session()->get('flash_warnings', []);
        $errors = session()->get('flash_errors', []);

        // Clear flash messages
        session()->forget(['flash_messages', 'flash_warnings', 'flash_errors']);

        // Prepare data for view
        $pageTitle = 'Setting Akun';

        return view('dashboard.setting_akun', compact(
            'pageTitle',
            'messages',
            'warnings',
            'errors',
            'userDb',
            'medicName',
            'medicPos',
            'medicBatch',
            'nomorInduk',
            'tanggalMasuk',
            'batchLocked',
            'kodeBatch'
        ));
    }

    /**
     * Process setting akun form
     * POST /dashboard/settings/akun
     */
    public function akunAction(Request $request)
    {
        // Get user from session (mirror legacy)
        $user = session('user_rh', []);
        $userId = $user['id'] ?? 0;
        $currentName = $user['full_name'] ?? '';
        $currentPos = $user['position'] ?? '';
        $currentBatch = $user['batch'] ?? null;

        if ($userId <= 0) {
            return redirect()->back()->with('flash_errors', ['Session tidak valid. Silakan login ulang.']);
        }

        // Get form inputs (mirror legacy)
        $fullName = trim($request->input('full_name', ''));
        $position = trim($request->input('position', ''));
        $citizenId = trim($request->input('citizen_id', ''));
        $jenisKelamin = trim($request->input('jenis_kelamin', ''));
        $noHpIc = trim($request->input('no_hp_ic', ''));
        $oldPin = $request->input('old_pin', '');
        $newPin = $request->input('new_pin', '');
        $confirmPin = $request->input('confirm_pin', '');
        $batch = intval($request->input('batch', 0));
        $tanggalMasuk = $request->input('tanggal_masuk', null);

        // Validasi Citizen ID (mirror legacy - server side)
        $errors = [];

        if ($citizenId === '') {
            $errors[] = 'Citizen ID wajib diisi.';
        } else {
            // Hapus spasi
            $citizenId = str_replace(' ', '', $citizenId);
            // Convert ke uppercase
            $citizenId = strtoupper($citizenId);

            // Validasi format
            if (!preg_match('/^[A-Z0-9]+$/', $citizenId)) {
                $errors[] = 'Citizen ID hanya boleh berisi HURUF BESAR dan ANGKA, tanpa spasi atau karakter khusus.';
            } elseif (strlen($citizenId) < 6) {
                $errors[] = 'Citizen ID minimal 6 karakter.';
            } elseif (!preg_match('/\d/', $citizenId)) {
                $errors[] = 'Citizen ID harus mengandung minimal 1 angka.';
            } elseif (!preg_match('/[A-Z]/', $citizenId)) {
                $errors[] = 'Citizen ID harus mengandung minimal 1 huruf.';
            } elseif (preg_match('/^[A-Z]+$/', $citizenId)) {
                $errors[] = 'Citizen ID tidak boleh hanya huruf saja. Harus kombinasi huruf dan angka.';
            } elseif (preg_match('/^[0-9]+$/', $citizenId)) {
                $errors[] = 'Citizen ID tidak boleh hanya angka saja. Harus kombinasi huruf dan angka.';
            } else {
                // Validasi tidak sama dengan nama
                $fullNameClean = strtoupper(str_replace(' ', '', $fullName));
                if ($citizenId === $fullNameClean) {
                    $errors[] = 'Citizen ID tidak boleh sama dengan Nama Medis. Contoh format yang benar: RH39IQLC';
                }
            }
        }

        // Validasi dasar (mirror legacy)
        if ($noHpIc === '') {
            $errors[] = 'No HP IC wajib diisi.';
        }

        if ($fullName === '' || $position === '') {
            $errors[] = 'Nama dan Jabatan wajib diisi.';
        }

        if (!in_array($jenisKelamin, ['Laki-laki', 'Perempuan'], true)) {
            $errors[] = 'Jenis kelamin wajib dipilih.';
        }

        if ($batch <= 0) {
            $errors[] = 'Batch tidak valid.';
        }

        if (empty($tanggalMasuk)) {
            $errors[] = 'Tanggal masuk wajib diisi.';
        }

        // Validasi PIN (mirror legacy)
        $willChangePin = ($oldPin !== '' || $newPin !== '' || $confirmPin !== '');

        if ($willChangePin) {
            if ($oldPin === '' || $newPin === '' || $confirmPin === '') {
                $errors[] = 'Jika ingin mengganti PIN, semua field PIN harus diisi.';
            } elseif (!preg_match('/^\d{4}$/', $oldPin)) {
                $errors[] = 'PIN lama harus 4 digit angka.';
            } elseif (!preg_match('/^\d{4}$/', $newPin)) {
                $errors[] = 'PIN baru harus 4 digit angka.';
            } elseif ($newPin !== $confirmPin) {
                $errors[] = 'Konfirmasi PIN baru tidak sama.';
            } elseif ($oldPin === $newPin) {
                $errors[] = 'PIN baru tidak boleh sama dengan PIN lama.';
            } else {
                // Verifikasi PIN lama
                $currentPinHash = DB::table('user_rh')->where('id', $userId)->value('pin');
                if (!password_verify($oldPin, $currentPinHash)) {
                    $errors[] = 'PIN lama salah.';
                }
            }
        }

        // Jika ada error, redirect back
        if (!empty($errors)) {
            return redirect()->back()
                ->with('flash_errors', $errors)
                ->withInput();
        }

        // Fix batch logic (mirror legacy)
        $batchFromDb = (int)($currentBatch ?? 0);
        if ($batchFromDb > 0) {
            $batch = $batchFromDb;
        }

        // Get current user data
        $userDb = DB::table('user_rh')
            ->select(
                'file_ktp',
                'file_sim',
                'file_kta',
                'file_skb',
                'sertifikat_heli',
                'sertifikat_operasi',
                'dokumen_lainnya'
            )
            ->where('id', $userId)
            ->first();

        $currentKodeInduk = DB::table('user_rh')
            ->where('id', $userId)
            ->value('kode_nomor_induk_rs');

        // Generate kode nomor induk (mirror legacy logic)
        $kodeNomorInduk = null;

        if (empty($currentKodeInduk)) {
            $batchCode = chr(64 + $batch); // 1=A
            $idPart = str_pad($userId, 2, '0', STR_PAD_LEFT);

            $nameParts = preg_split('/\s+/', strtoupper($fullName));
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[count($nameParts) - 1] ?? '';

            $letters = substr($firstName, 0, 2) . substr($lastName, 0, 2);

            $nameCodes = [];
            foreach (str_split($letters) as $char) {
                $pos = ord(strtoupper($char)) - 64;
                if ($pos >= 1 && $pos <= 26) {
                    $nameCodes[] = str_pad($pos, 2, '0', STR_PAD_LEFT);
                }
            }

            $kodeNomorInduk = 'RH' . $batchCode . '-' . $idPart . implode('', $nameCodes);
        }

        // Process file uploads (mirror legacy)
        $uploadedPaths = [];
        $docFields = [
            'file_ktp',
            'file_sim',
            'file_kta',
            'file_skb',
            'sertifikat_heli',
            'sertifikat_operasi',
            'dokumen_lainnya'
        ];

        foreach ($docFields as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $file = $request->file($field);

                // Validate file type
                $allowedMimes = ['image/jpeg', 'image/png'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return redirect()->back()
                        ->with('flash_errors', ["File {$field} harus JPG atau PNG."]);
                }

                // Delete old file if exists
                if (!empty($userDb->$field)) {
                    $oldPath = public_path($userDb->$field);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // Create folder
                $kodeMedis = $currentKodeInduk ?? $kodeNomorInduk ?? 'no-kode';
                $folderName = 'user_' . $userId . '-' . strtolower($kodeMedis);
                $uploadDir = public_path('storage/user_docs/' . $folderName);

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Compress and save image (mirror legacy logic)
                $ext = $file->getMimeType() === 'image/png' ? 'png' : 'jpg';
                $fileName = $field . '.' . $ext;
                $finalPath = $uploadDir . '/' . $fileName;

                // Compress image (simplified version)
                $this->compressImageSmart(
                    $file->getPathname(),
                    $finalPath,
                    1200,
                    300000,
                    70
                );

                $uploadedPaths[$field] = 'storage/user_docs/' . $folderName . '/' . $fileName;
            }
        }

        // Update user data (mirror legacy)
        $updateData = [
            'full_name' => $fullName,
            'position' => $position,
            'tanggal_masuk' => $tanggalMasuk,
            'citizen_id' => $citizenId,
            'jenis_kelamin' => $jenisKelamin,
            'no_hp_ic' => $noHpIc,
        ];

        // Update batch hanya jika sebelumnya kosong
        if ($batchFromDb === 0) {
            $updateData['batch'] = $batch;
        }

        // Add uploaded file paths
        foreach ($uploadedPaths as $col => $path) {
            $updateData[$col] = $path;
        }

        // Add kode nomor induk if generated
        if ($kodeNomorInduk !== null) {
            $updateData['kode_nomor_induk_rs'] = $kodeNomorInduk;
        }

        // Update PIN if changed
        $pinChanged = 0;
        if ($willChangePin && $newPin !== '') {
            $updateData['pin'] = password_hash($newPin, PASSWORD_BCRYPT);
            $pinChanged = 1;
        }

        try {
            DB::table('user_rh')
                ->where('id', $userId)
                ->update($updateData);

            // Update session (mirror legacy)
            $updatedUser = DB::table('user_rh')->where('id', $userId)->first();
            session()->put('user_rh', (array)$updatedUser);

        } catch (\Exception $e) {
            // Handle duplicate entry
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return redirect()->back()
                    ->with('flash_errors', ['Nama sudah digunakan oleh user lain.']);
            }

            return redirect()->back()
                ->with('flash_errors', ['Terjadi kesalahan saat menyimpan data.']);
        }

        // Flash message (mirror legacy)
        if ($pinChanged) {
            return redirect()->back()
                ->with('flash_messages', ['Akun dan PIN berhasil diperbarui.']);
        } else {
            return redirect()->back()
                ->with('flash_messages', ['Akun berhasil diperbarui.']);
        }
    }

    /**
     * Display spreadsheet setting page
     * GET /dashboard/settings/spreadsheet
     */
    public function spreadsheet()
    {
        $role = strtolower(session('user_rh')['role'] ?? '');
        if ($role === 'staff') {
            return redirect()->route('dashboard');
        }

        $configFile = base_path('database/sheet_config.json');
        $sheetConfig = [
            'spreadsheet_id' => '',
            'sheet_gid'      => '',
        ];

        if (file_exists($configFile)) {
            $data = json_decode(file_get_contents($configFile), true);
            if (is_array($data)) {
                $sheetConfig = array_merge($sheetConfig, $data);
            }
        }

        $currentCsvUrl = ($sheetConfig['spreadsheet_id'] && $sheetConfig['sheet_gid'])
            ? sprintf(
                'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s',
                $sheetConfig['spreadsheet_id'],
                $sheetConfig['sheet_gid']
            )
            : '';

        $sheetEditUrl = ($sheetConfig['spreadsheet_id'] && $sheetConfig['sheet_gid'])
            ? sprintf(
                'https://docs.google.com/spreadsheets/d/%s/edit?gid=%s#gid=%s',
                $sheetConfig['spreadsheet_id'],
                $sheetConfig['sheet_gid'],
                $sheetConfig['sheet_gid']
            )
            : '';

        return view('dashboard.setting_spreadsheet', [
            'sheetConfig' => $sheetConfig,
            'currentCsvUrl' => $currentCsvUrl,
            'sheetEditUrl' => $sheetEditUrl,
            'pageTitle' => 'Setting Spreadsheet'
        ]);
    }

    /**
     * Process spreadsheet setting
     * POST /dashboard/settings/spreadsheet
     */
    public function spreadsheetAction(Request $request)
    {
        $spreadsheetId = trim($request->input('spreadsheet_id', ''));
        $sheetGid = trim($request->input('sheet_gid', ''));

        if ($spreadsheetId === '' || $sheetGid === '') {
            return redirect()->back()->with('flash_errors', ['Spreadsheet ID dan Sheet GID wajib diisi.']);
        }

        $data = [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_gid'      => $sheetGid,
        ];

        $configFile = base_path('database/sheet_config.json');
        if (file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            return redirect()->back()->with('flash_errors', ['Gagal menyimpan konfigurasi spreadsheet.']);
        }

        return redirect()->route('settings.spreadsheet')->with('flash_messages', ['Konfigurasi Spreadsheet berhasil disimpan.']);
    }

    /**
     * Sync from Google Sheet
     * POST /dashboard/settings/sync-sheet
     */
    public function syncSheet()
    {
        // Mirror legacy/dashboard/sync_from_sheet.php
        $configFile = base_path('database/sheet_config.json');
        if (!file_exists($configFile)) {
            return redirect()->back()->with('flash_errors', ['Spreadsheet belum dikonfigurasi.']);
        }

        $sheetConfig = json_decode(file_get_contents($configFile), true);
        if (!$sheetConfig['spreadsheet_id'] || !$sheetConfig['sheet_gid']) {
            return redirect()->back()->with('flash_errors', ['Spreadsheet ID / GID belum diset.']);
        }

        $csvUrl = sprintf(
            'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s',
            $sheetConfig['spreadsheet_id'],
            $sheetConfig['sheet_gid']
        );

        $csvContent = @file_get_contents($csvUrl);
        if ($csvContent === false) {
            return redirect()->back()->with('flash_errors', ['Gagal ambil CSV Google Sheets.']);
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($csvContent));
        $lineNum = 0;
        $imported = 0;
        $skipped = 0;
        $duplicate = 0;
        $maxRow = 900;

        foreach ($lines as $line) {
            $lineNum++;
            if ($lineNum < 3 || $lineNum > $maxRow) continue;
            if (trim($line) === '') {
                $skipped++;
                continue;
            }

            $cols = str_getcsv($line);
            if (count($cols) < 4) {
                $skipped++;
                continue;
            }

            [$buyerName, $medicName, $jabatan, $jenisPaket, $totalHarga] = array_map('trim', $cols + ['', '', '', '', '']);
            if (!$buyerName || !$medicName || !$jabatan || !$jenisPaket) {
                $skipped++;
                continue;
            }

            // Cari paket
            $pkg = DB::table('packages')->where('name', $jenisPaket)->first();
            if (!$pkg) {
                $skipped++;
                continue;
            }

            $price = (int)$pkg->price;
            if ($totalHarga !== '') {
                $normPrice = str_replace(['.', ','], '', $totalHarga);
                if (is_numeric($normPrice)) $price = (int)$normPrice;
            }

            $norm = function ($v) {
                return preg_replace('/\s+/', ' ', strtolower(trim($v)));
            };

            $txHash = hash(
                'sha256',
                $norm($buyerName) . '|' .
                $norm($medicName) . '|' .
                $norm($jabatan) . '|' .
                $norm($pkg->name) . '|' .
                $price
            );

            // Cek duplikat
            $exists = DB::table('sales')->where('tx_hash', $txHash)->exists();
            if ($exists) {
                $duplicate++;
                continue;
            }

            // Insert
            DB::table('sales')->insert([
                'consumer_name' => $buyerName,
                'medic_name' => $medicName,
                'medic_jabatan' => $jabatan,
                'package_id' => $pkg->id,
                'package_name' => $pkg->name,
                'price' => $price,
                'qty_bandage' => (int)$pkg->bandage_qty,
                'qty_ifaks' => (int)$pkg->ifaks_qty,
                'qty_painkiller' => (int)$pkg->painkiller_qty,
                'tx_hash' => $txHash,
                'created_at' => now(),
            ]);

            $imported++;
        }

        return redirect()->back()->with('flash_messages', ["DONE | Rows: {$lineNum} | Imported: {$imported} | Skipped: {$skipped} | Duplicate: {$duplicate}"]);
    }
}
