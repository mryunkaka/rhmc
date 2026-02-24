<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\UserRh;
use App\Models\RememberToken;

class AuthController extends Controller
{
    /**
     * Show login page
     * Mirror of: legacy/auth/login.php
     */
    public function showLogin(Request $request)
    {
        // Check if already logged in
        if (session()->has('user_rh')) {
            $user = session('user_rh');
            $position = strtolower(trim($user['position'] ?? ''));

            if ($position === 'trainee') {
                return redirect('/dashboard');
            }

            return redirect('/dashboard/rekap-farmasi');
        }

        // Check for remember token
        if (!$request->hasCookie('remember_login')) {
            return view('auth.login');
        }

        // Verify remember token
        $cookie = $request->cookie('remember_login');
        $parts = explode(':', $cookie, 2);

        if (count($parts) !== 2) {
            return view('auth.login');
        }

        [$userId, $token] = $parts;

        $rememberTokens = RememberToken::where('user_id', $userId)
            ->where('expired_at', '>', now())
            ->get();

        foreach ($rememberTokens as $row) {
            if (password_verify($token, $row->token_hash)) {
                $user = UserRh::find($userId);

                if ($user) {
                    session([
                        'user_rh' => [
                            'id' => $user->id,
                            'name' => $user->full_name,
                            'role' => $user->role,
                            'position' => $user->position
                        ]
                    ]);

                    $position = strtolower(trim($user->position ?? ''));

                    if ($position === 'trainee') {
                        return redirect('/dashboard');
                    }

                    return redirect('/dashboard/rekap-farmasi');
                }
            }
        }

        // Invalid token - remove cookie
        return redirect('/login')
            ->withCookie(\cookie('remember_login', '', time() - 3600));
    }

    /**
     * Process login
     * Mirror of: legacy/auth/login_process.php
     */
    public function login(Request $request)
    {
        $force = $request->has('force_login');

        // Jika force login, ambil dari session
        if ($force && session()->has('pending_login')) {
            $fullName = session('pending_login.full_name');
            $pin = session('pending_login.pin');
            session()->forget('pending_login');
        } else {
            $fullName = trim($request->input('full_name', ''));
            $pin = trim($request->input('pin', ''));
        }

        // Validasi awal
        if ($fullName === '' || $pin === '') {
            return redirect('/login')->with('error', 'Form login tidak valid');
        }

        // Cari user
        $user = UserRh::where('full_name', $fullName)->first();

        if (!$user || !Hash::check($pin, $user->pin)) {
            return redirect('/login')->with('error', 'Nama atau PIN salah');
        }

        // Cek verifikasi akun
        if ((int)$user->is_verified === 0) {
            return redirect('/login')->with('error', 'Akun belum diverifikasi');
        }

        // Cek login di device lain
        $activeToken = RememberToken::where('user_id', $user->id)
            ->where('expired_at', '>', now())
            ->count();

        // Jika masih ada token aktif & belum force login
        if ($activeToken > 0 && !$force) {
            session([
                'pending_login' => [
                    'full_name' => $fullName,
                    'pin' => $pin
                ]
            ]);

            return redirect('/login?confirm=1');
        }

        // Paksa logout device lain (hapus semua token lama)
        RememberToken::where('user_id', $user->id)->delete();

        // Set session login
        session([
            'user_rh' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'role' => $user->role,
                'position' => $user->position
            ]
        ]);

        // Simpan remember token baru (1 tahun)
        $token = bin2hex(random_bytes(32));
        $hash = Hash::make($token);
        $exp = now()->addDays(365);

        RememberToken::create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'expired_at' => $exp
        ]);

        // Set cookie
        $cookie = \cookie(
            'remember_login',
            $user->id . ':' . $token,
            86400 * 365, // 1 tahun dalam detik
            '/',
            '',
            false,
            true // httponly
        );

        // Redirect berdasarkan position
        $position = strtolower(trim($user->position ?? ''));

        if ($position === 'trainee') {
            return redirect('/dashboard')->withCookie($cookie);
        }

        return redirect('/dashboard/rekap-farmasi')->withCookie($cookie);
    }

    /**
     * Process registration
     * Mirror of: legacy/auth/register_process.php
     */
    public function register(Request $request)
    {
        $name = trim($request->input('full_name', ''));
        $pin = trim($request->input('pin', ''));
        $citizenId = trim($request->input('citizen_id', ''));
        $noHpIc = trim($request->input('no_hp_ic', ''));
        $jenisKelamin = $request->input('jenis_kelamin', '');
        $batch = intval($request->input('batch', 0));
        $role = $request->input('role', 'Staff');

        // Default position
        $position = 'Trainee';

        // Validasi
        if ($name === '' || !preg_match('/^\d{4}$/', $pin)) {
            return redirect('/login')->with('error', 'Data registrasi tidak valid');
        }

        if ($batch < 1 || $batch > 26) {
            return redirect('/login')->with('error', 'Batch tidak valid');
        }

        if ($citizenId === '' || $noHpIc === '' || $jenisKelamin === '') {
            return redirect('/login')->with('error', 'Data pribadi wajib diisi');
        }

        if (!in_array($jenisKelamin, ['Laki-laki', 'Perempuan'], true)) {
            return redirect('/login')->with('error', 'Jenis kelamin tidak valid');
        }

        // Cek citizen ID duplikat
        if (UserRh::where('citizen_id', $citizenId)->exists()) {
            return redirect('/login')->with('error', 'Citizen ID sudah terdaftar');
        }

        // Validasi file KTP
        if (!$request->hasFile('file_ktp') || !$request->file('file_ktp')->isValid()) {
            return redirect('/login')->with('error', 'KTP wajib diunggah');
        }

        $ktpFile = $request->file('file_ktp');
        $ktpInfo = getimagesize($ktpFile->getPathname());

        if (!$ktpInfo || !in_array($ktpInfo['mime'], ['image/jpeg', 'image/png'], true)) {
            return redirect('/login')->with('error', 'File KTP harus JPG atau PNG');
        }

        // Cek nama duplikat
        if (UserRh::where('full_name', $name)->exists()) {
            return redirect('/login')->with('error', 'Nama sudah terdaftar');
        }

        $isVerified = ($role === 'Staff') ? 1 : 0;

        // Insert user
        $user = UserRh::create([
            'full_name' => $name,
            'pin' => Hash::make($pin),
            'position' => $position,
            'role' => $role,
            'batch' => $batch,
            'citizen_id' => $citizenId,
            'no_hp_ic' => $noHpIc,
            'jenis_kelamin' => $jenisKelamin,
            'is_verified' => $isVerified
        ]);

        $userId = $user->id;

        // Generate kode nomor induk RS
        $batchCode = chr(64 + $batch); // 1 = A
        $idPart = str_pad($userId, 2, '0', STR_PAD_LEFT);

        $nameParts = preg_split('/\s+/', strtoupper($name));
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[count($nameParts) - 1] ?? '';

        $letters = substr($firstName, 0, 2) . substr($lastName, 0, 2);

        $nameCodes = [];
        foreach (str_split($letters) as $char) {
            $pos = $this->alphaPos($char);
            if ($pos !== null) {
                $nameCodes[] = $this->twoDigit($pos);
            }
        }

        $kodeNomorInduk = 'RH' . $batchCode . '-' . $idPart . implode('', $nameCodes);

        // Buat folder untuk dokumen
        $folderName = 'user_' . $userId . '-' . strtolower($kodeNomorInduk);
        $baseDir = public_path('storage/user_docs/');
        $uploadDir = $baseDir . $folderName;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return redirect('/login')->with('error', 'Gagal membuat folder dokumen');
        }

        // Upload dokumen
        $docFields = ['file_ktp', 'file_sim', 'file_skb'];
        $uploadedPaths = [];

        foreach ($docFields as $field) {
            if (!$request->hasFile($field) || !$request->file($field)->isValid()) {
                continue;
            }

            $file = $request->file($field);
            $info = getimagesize($file->getPathname());

            if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
                return redirect('/login')->with('error', "File {$field} harus JPG atau PNG");
            }

            $ext = $info['mime'] === 'image/png' ? 'png' : 'jpg';
            $finalPath = $uploadDir . '/' . $field . '.' . $ext;

            // Compress dan simpan
            $this->compressImageSmart($file->getPathname(), $finalPath);

            $uploadedPaths[$field] = 'storage/user_docs/' . $folderName . '/' . $field . '.' . $ext;
        }

        // Update user dengan kode nomor induk dan path dokumen
        $user->kode_nomor_induk_rs = $kodeNomorInduk;

        foreach ($uploadedPaths as $field => $path) {
            $user->{$field} = $path;
        }

        $user->save();

        return redirect('/login')->with('success', 'Registrasi berhasil');
    }

    /**
     * Process logout
     * Mirror of: legacy/auth/logout.php
     */
    public function logout(Request $request)
    {
        if (session()->has('user_rh')) {
            $userId = session('user_rh.id');

            // Hapus remember tokens
            RememberToken::where('user_id', $userId)->delete();
        }

        // Hapus cookie
        $cookie = cookie()->forget('remember_login');

        // Destroy session
        session()->flush();
        session()->regenerate();

        return redirect('/login')->withCookie($cookie);
    }

    /**
     * Check session validity (AJAX)
     * Mirror of: legacy/auth/check_session.php
     */
    public function checkSession(Request $request)
    {
        $valid = false;

        if (session()->has('user_rh')) {
            $valid = true;
        } elseif ($request->hasCookie('remember_login')) {
            $cookie = $request->cookie('remember_login');
            $parts = explode(':', $cookie, 2);

            if (count($parts) !== 2) {
                session()->flush();
                return response()
                    ->json(['valid' => false])
                    ->withCookie(cookie()->forget('remember_login'));
            }

            [$userId, $token] = $parts;

            $rememberTokens = RememberToken::where('user_id', $userId)
                ->where('expired_at', '>', now())
                ->get();

            foreach ($rememberTokens as $row) {
                if (password_verify($token, $row->token_hash)) {
                    $user = UserRh::find($userId);

                    if ($user) {
                        session([
                            'user_rh' => [
                                'id' => $user->id,
                                'name' => $user->full_name,
                                'role' => $user->role,
                                'position' => $user->position
                            ]
                        ]);

                        $valid = true;
                    }
                    break;
                }
            }
        }

        if (!$valid) {
            session()->flush();
        }

        return response()->json(['valid' => $valid]);
    }

    /**
     * Helper: Alpha position
     */
    private function alphaPos($char)
    {
        $char = strtoupper($char);
        if ($char < 'A' || $char > 'Z') return null;
        return ord($char) - 64;
    }

    /**
     * Helper: Two digit
     */
    private function twoDigit($num)
    {
        return str_pad($num, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Helper: Compress image smart
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
