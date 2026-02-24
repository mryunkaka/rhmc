<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IdentityMaster;
use App\Models\IdentityVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IdentityTestController extends Controller
{
    private const OCR_API_KEY = 'K85527757488957';
    private const MAX_WIDTH = 1200;
    private const TARGET_FILE_SIZE = 307200; // 300KB
    private const MIN_QUALITY = 70;

    /**
     * Display Identity Test Page
     * Mirror of: legacy/dashboard/identity_test.php
     */
    public function index()
    {
        $user = session('user_rh');

        return view('dashboard.identity_test', [
            'pageTitle' => 'Identity OCR Scanner',
            'medicName' => $user['name'] ?? 'User',
            'medicPos' => $user['position'] ?? '-',
        ]);
    }

    /**
     * Handle OCR Ajax Request
     * Mirror of: legacy/dashboard/identity_test.php POST action: ocr_ajax
     */
    public function ocrAjax(Request $request)
    {
        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'File tidak ditemukan']);
        }

        $file = $request->file('image');
        $tmpPath = $file->getPathname();

        $mime = $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png'];

        if (!in_array($mime, $allowed, true)) {
            return response()->json(['error' => 'Format gambar tidak didukung (hanya JPG/PNG)']);
        }

        // Compress Image
        $src = imagecreatefromstring(file_get_contents($tmpPath));
        if (!$src) {
            return response()->json(['error' => 'Gambar tidak valid']);
        }

        $w = imagesx($src);
        $h = imagesy($src);

        if ($w > self::MAX_WIDTH) {
            $ratio = self::MAX_WIDTH / $w;
            $nw = self::MAX_WIDTH;
            $nh = (int)($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
        }
        else {
            $dst = $src;
        }

        $uploadDir = public_path('storage/identity/');
        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0777, true);
        }

        $tmpFileName = 'tmp_' . uniqid() . '.jpg';
        $tmpFile = $uploadDir . $tmpFileName;

        imageinterlace($dst, true);
        $this->compressImageInternal($dst, $tmpFile, self::TARGET_FILE_SIZE, self::MIN_QUALITY);
        imagedestroy($dst);

        // OCR API Call
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.ocr.space/parse/image',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'apikey' => self::OCR_API_KEY,
                'language' => 'eng',
                'OCREngine' => '2',
                'scale' => 'true',
                'isTable' => 'true',
                'detectOrientation' => 'true',
                'isOverlayRequired' => 'false',
                'file' => new \CURLFile($tmpFile)
            ]
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);

        if ($httpCode !== 200 || !isset($json['ParsedResults'])) {
            return response()->json([
                'error' => 'OCR API error',
                'debug' => $json
            ]);
        }

        $parsed = $json['ParsedResults'][0] ?? [];

        if (isset($parsed['ErrorMessage']) && $parsed['ErrorMessage']) {
            return response()->json([
                'error' => $parsed['ErrorMessage'],
                'debug' => $json
            ]);
        }

        $text = trim($parsed['ParsedText'] ?? '');

        if ($text === '' && !empty($parsed['TextOverlay']['Lines'])) {
            $lines = [];
            foreach ($parsed['TextOverlay']['Lines'] as $line) {
                $lineText = [];
                foreach ($line['Words'] as $word) {
                    $lineText[] = $word['WordText'];
                }
                $lines[] = implode(' ', $lineText);
            }
            $text = implode("\n", $lines);
        }

        if ($text === '') {
            @unlink($tmpFile);
            return response()->json(['error' => 'OCR tidak menghasilkan teks', 'debug' => $json]);
        }

        $textUpper = strtoupper($text);

        $data = [
            'first_name' => $this->extractField($textUpper, [
                '/FIRST\s*NAME[:\s]+([A-Z]+)/i',
                '/FIRSTNAME[:\s]+([A-Z]+)/i',
                '/FIRST[:\s]+([A-Z]+)/i'
            ]),
            'last_name' => $this->extractField($textUpper, [
                '/LAST\s*NAME[:\s]+([A-Z]+)/i',
                '/LASTNAME[:\s]+([A-Z]+)/i',
                '/LAST[:\s]+([A-Z]+)/i',
                '/SURNAME[:\s]+([A-Z]+)/i'
            ]),
            'dob' => $this->extractField($text, [
                '/DOB[:\s]+([\d\-\/]+)/i',
                '/DATE\s*OF\s*BIRTH[:\s]+([\d\-\/]+)/i',
                '/BIRTH[:\s]+([\d\-\/]+)/i',
                '/(\d{4}-\d{2}-\d{2})/',
                '/(\d{2}[-\/]\d{2}[-\/]\d{4})/',
            ]),
            'sex' => $this->extractField($textUpper, [
                '/SEX[:\s]+([MF])/i',
                '/GENDER[:\s]+([MF])/i',
                '/\b(MALE|FEMALE)\b/i'
            ]),
            'nationality' => $this->extractField($textUpper, [
                '/NATIONALITY[:\s]+([A-Z]+)/i',
                '/CITIZEN[:\s]+([A-Z]+)/i'
            ]),
            'citizen_id' => $this->extractField($text, [
                '/CITIZEN\s*ID[:\s]+([A-Z0-9]+)/i',
                '/ID[:\s]+([A-Z0-9]{8,18})/i',
                '/\b([A-Z]{1,3}\d{5,}[A-Z0-9]*)\b/',
                '/\b([Y]\d[A-Z0-9]{6,})\b/',
            ])
        ];

        if ($data['sex']) {
            $data['sex'] = strtoupper(substr($data['sex'], 0, 1));
            if ($data['sex'] !== 'M' && $data['sex'] !== 'F') {
                $data['sex'] = '';
            }
        }

        if ($data['dob'] && strpos($data['dob'], '/') !== false) {
            $parts = explode('/', $data['dob']);
            if (count($parts) === 3) {
                // Determine format
                if (strlen($parts[2]) === 4) { // DD/MM/YYYY
                    $data['dob'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            }
        }

        $data['temp_file'] = $tmpFile;
        $data['compressed_size'] = round(filesize($tmpFile) / 1024, 2) . ' KB';

        return response()->json($data);
    }

    /**
     * Handle Save Base64 Image
     * Mirror of: legacy/dashboard/identity_test.php POST action: save_base64
     */
    public function saveBase64(Request $request)
    {
        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'File tidak ditemukan']);
        }

        $file = $request->file('image');
        $tmpPath = $file->getPathname();

        $src = imagecreatefromstring(file_get_contents($tmpPath));
        if (!$src) {
            return response()->json(['error' => 'Gambar tidak valid']);
        }

        $w = imagesx($src);
        $h = imagesy($src);

        if ($w > self::MAX_WIDTH) {
            $ratio = self::MAX_WIDTH / $w;
            $nw = self::MAX_WIDTH;
            $nh = (int)($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
        }
        else {
            $dst = $src;
        }

        $uploadDir = public_path('storage/identity/');
        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0777, true);
        }

        $tmpFile = $uploadDir . 'tmp_manual_' . uniqid() . '.jpg';

        imageinterlace($dst, true);
        $this->compressImageInternal($dst, $tmpFile, self::TARGET_FILE_SIZE, self::MIN_QUALITY);
        imagedestroy($dst);

        return response()->json([
            'success' => true,
            'temp_file' => $tmpFile,
            'compressed_size' => round(filesize($tmpFile) / 1024, 2) . ' KB'
        ]);
    }

    /**
     * Handle Save Identity
     * Mirror of: legacy/dashboard/identity_test.php POST action: save_identity
     */
    public function saveIdentity(Request $request)
    {
        $citizenId = trim($request->input('citizen_id', ''));
        $tempFile = trim($request->input('temp_file', ''));

        if (empty($citizenId)) {
            return response()->json(['error' => 'Citizen ID tidak boleh kosong']);
        }

        if (empty($tempFile) || !File::exists($tempFile)) {
            return response()->json(['error' => 'File temporary tidak ditemukan, silakan upload ulang']);
        }

        DB::beginTransaction();

        try {
            $master = IdentityMaster::where('citizen_id', $citizenId)->first();

            $data = $request->only(['first_name', 'last_name', 'dob', 'sex', 'nationality', 'change_reason']);
            foreach ($data as $key => $val) {
                $data[$key] = trim($val);
            }

            $dataSame = false;
            if ($master && $this->isDataSameInternal($master, $data)) {
                $dataSame = true;
            }

            $citizenFolder = public_path('storage/identity/' . $citizenId . '/');
            if (!File::exists($citizenFolder)) {
                File::makeDirectory($citizenFolder, 0777, true);
            }

            if ($dataSame) {
                $finalRelativePath = $master->image_path;
                @unlink($tempFile);
                $identityId = $master->id;
                $versionId = $master->active_version_id;
            }
            else {
                $versionNumber = IdentityVersion::where('citizen_id', $citizenId)->count() + 1;
                $versionFilename = 'v' . $versionNumber . '.jpg';
                $finalPath = $citizenFolder . $versionFilename;
                $finalRelativePath = 'storage/identity/' . $citizenId . '/' . $versionFilename;

                File::copy($tempFile, $finalPath);
                @unlink($tempFile);

                if ($master) {
                    $identityId = $master->id;
                    $master->update([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'dob' => $data['dob'],
                        'sex' => $data['sex'],
                        'nationality' => $data['nationality'],
                        'image_path' => $finalRelativePath,
                    ]);
                }
                else {
                    $master = IdentityMaster::create([
                        'citizen_id' => $citizenId,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'dob' => $data['dob'],
                        'sex' => $data['sex'],
                        'nationality' => $data['nationality'],
                        'image_path' => $finalRelativePath,
                    ]);
                    $identityId = $master->id;
                }

                $userId = session('user_rh.id');
                $changeReason = $data['change_reason'];
                if (empty($changeReason)) {
                    $changeReason = $master->wasRecentlyCreated ? 'Initial OCR scan (v1)' : 'Data updated via OCR (v' . $versionNumber . ')';
                }

                $version = IdentityVersion::create([
                    'identity_id' => $identityId,
                    'citizen_id' => $citizenId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'dob' => $data['dob'],
                    'sex' => $data['sex'],
                    'nationality' => $data['nationality'],
                    'image_path' => $finalRelativePath,
                    'change_reason' => $changeReason,
                    'changed_by' => $userId
                ]);

                $versionId = $version->id;
                $master->update(['active_version_id' => $versionId]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $dataSame ? 'Data sama dengan sebelumnya' : 'Data berhasil disimpan',
                'image_path' => $finalRelativePath,
                'data_same' => $dataSame,
                'new_version' => !$dataSame,
                'identity_id' => $identityId,
                'version_id' => $versionId
            ]);

        }
        catch (\Exception $e) {
            DB::rollBack();
            @unlink($tempFile);
            return response()->json([
                'error' => 'Gagal menyimpan identitas',
                'detail' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Check Identity Existence
     * Mirror of: legacy/dashboard/identity_test.php POST action: check_identity
     */
    public function checkIdentity(Request $request)
    {
        $citizenId = trim($request->input('citizen_id', ''));
        $firstName = trim($request->input('first_name', ''));
        $lastName = trim($request->input('last_name', ''));

        if (empty($citizenId)) {
            return response()->json(['error' => 'Citizen ID tidak boleh kosong']);
        }

        try {
            $master = IdentityMaster::where('citizen_id', $citizenId)->first();

            if (!$master) {
                return response()->json([
                    'exists' => false,
                    'message' => 'Data baru, silakan simpan'
                ]);
            }

            $nameChanged = (
                $master->first_name !== $firstName ||
                $master->last_name !== $lastName
                );

            if ($nameChanged) {
                return response()->json([
                    'exists' => true,
                    'name_changed' => true,
                    'message' => 'Citizen ID sama tetapi nama berbeda',
                    'old_data' => [
                        'first_name' => $master->first_name,
                        'last_name' => $master->last_name,
                        'dob' => $master->dob,
                        'sex' => $master->sex,
                        'nationality' => $master->nationality
                    ],
                    'new_data' => [
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ]
                ]);
            }

            return response()->json([
                'exists' => true,
                'name_changed' => false,
                'auto_close' => true,
                'message' => 'Data sudah ada dan sama persis',
                'identity_id' => $master->id,
                'data' => $master
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengecek database',
                'detail' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Delete Temp File
     * Mirror of: legacy/dashboard/identity_test.php POST action: delete_temp
     */
    public function deleteTemp(Request $request)
    {
        $tempFile = trim($request->input('temp_file', ''));

        if (!$tempFile) {
            return response()->json(['error' => 'Temp file kosong']);
        }

        $realBase = realpath(public_path('storage/identity/'));
        $realFile = realpath($tempFile);

        if ($realFile && strpos($realFile, $realBase) === 0 && File::exists($realFile)) {
            File::delete($realFile);
            return response()->json(['success' => true, 'message' => 'Temp file dihapus']);
        }
        else {
            return response()->json(['error' => 'File tidak valid atau sudah terhapus']);
        }
    }

    /* ===============================
     PRIVATE HELPERS (MIRRORED LOGIC)
     =============================== */

    private function compressImageInternal($image, $targetPath, $targetSize, $minQuality = 70)
    {
        $quality = 90;
        $step = 5;

        while ($quality >= $minQuality) {
            imagejpeg($image, $targetPath, $quality);
            $fileSize = filesize($targetPath);

            if ($fileSize <= $targetSize) {
                return ['success' => true, 'quality' => $quality, 'size' => $fileSize];
            }

            $quality -= $step;
        }

        return ['success' => false, 'quality' => $minQuality, 'size' => filesize($targetPath)];
    }

    private function extractField($text, $patterns)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return trim($m[1]);
            }
        }
        return '';
    }

    private function isDataSameInternal($master, $newData)
    {
        return (
            $master->first_name === $newData['first_name'] &&
            $master->last_name === $newData['last_name'] &&
            $master->dob === $newData['dob'] &&
            $master->sex === $newData['sex'] &&
            $master->nationality === $newData['nationality']
            );
    }
}
