# LEGACY ANALYSIS - RHMC PHP NATIVE

**Tanggal Scan:** 2026-02-22
**Total File PHP:** ~1361 file
**File Accessible:** ~55 file

---

## 1. ROUTING - FILE YANG DIAKSES LANGSUNG

### Root
- `/index.php` → Redirect ke `/dashboard/rekap_farmasi.php`

### Authentication (`/auth/`)
| File | Method | Keterangan |
|------|--------|-----------|
| `login.php` | GET | Halaman login + register form |
| `login_process.php` | POST | Handler login (verify PIN, set session) |
| `logout.php` | GET/POST | Handler logout |
| `register_process.php` | POST | Handler registrasi user baru |
| `check_session.php` | GET | AJAX - cek validitas session (JSON) |
| `csrf.php` | - | CSRF helpers (tidak diakses langsung) |

### Dashboard (`/dashboard/`)
Total **42 file** - Semua memerlukan authentication via `auth_guard.php`

**File Utama:**
- `index.php` - Dashboard utama dengan statistik
- `rekap_farmasi.php` - Rekap transaksi farmasi (Trainee FORBIDDEN)
- `dashboard_data.php` - Data provider (di-include, bukan diakses langsung)

**File Lainnya:**
```
absensi_ems.php
candidates.php, candidate_decision.php, candidate_detail.php, candidate_interview_multi.php
ems_services.php
events.php, event_action.php, event_manage.php, event_participants.php
gaji.php, gaji_action.php, gaji_generate_manual.php, gaji_pay_process.php
identity_test.php
konsumen.php
manage_users.php, manage_users_action.php
operasi_plastik.php, operasi_plastik_action.php
ranking.php
regulasi.php
reimbursement.php, reimbursement_action.php, reimbursement_delete.php, reimbursement_pay.php
rekap_delete_bulk.php
rekap_farmasi_v2.php
rekap_gaji.php
restaurant_consumption.php, restaurant_consumption_action.php
restaurant_settings.php, restaurant_settings_action.php
setting_akun.php, setting_akun_action.php
setting_spreadsheet.php
sync_from_sheet.php
validasi.php, validasi_action.php
```

### Public (`/public/`)
File-file ini **TIDAK** memerlukan authentication:
- `recruitment_form.php` - Form pendaftaran calon medis
- `recruitment_submit.php` - Handler submit recruitment
- `recruitment_done.php` - Halaman sukses recruitment
- `ai_test.php` - AI testing interface
- `ai_test_submit.php` - Handler AI test

### API (`/api/`)
- `sync_sales.php` - Sync sales ke spreadsheet (Bearer token auth)

---

## 2. AUTH & SESSION STRUCTURE

### Login Flow
```
1. User buka /auth/login.php
2. Submit form (full_name + 4-digit PIN)
3. POST ke /auth/login_process.php
4. Verify:
   - Query user_rh WHERE full_name = ?
   - password_verify(PIN, user.password)
   - Cek is_verified = 1
   - Cek active remember_tokens (double login protection)
5. Set session:
   $_SESSION['user_rh'] = {
       id, name (alias full_name), full_name, role, position,
       batch, tanggal_masuk, citizen_id, no_hp_ic,
       jenis_kelamin, kode_nomor_induk_rs
   }
6. Create remember_token (1 tahun)
7. Set cookie: remember_login = "user_id:token"
8. Redirect:
   - Trainee → /dashboard/index.php
   - Lainnya → /dashboard/rekap_farmasi.php
```

### Session Structure
```php
$_SESSION['user_rh'] = [
    'id' => int,
    'name' => string,          // backward compatibility (same as full_name)
    'full_name' => string,
    'role' => string,          // Staff/Staff Manager/Manager/Vice Director/Director
    'position' => string,      // Trainee/Medic/etc
    'batch' => int,
    'tanggal_masuk' => date,
    'citizen_id' => string,
    'no_hp_ic' => string,
    'jenis_kelamin' => string,
    'kode_nomor_induk_rs' => string
]
```

### Auth Guard (`auth/auth_guard.php`)
```php
// Dipanggil di AWAL setiap file dashboard
require_once __DIR__ . '/../auth/auth_guard.php';

// Logic:
1. Cek $_SESSION['user_rh'] → return jika ada
2. Jika tidak ada, cek cookie 'remember_login'
3. Parse: "user_id:token"
4. Verify token melawan remember_tokens.token_hash
5. Jika valid → restore session
6. Jika invalid → redirect /auth/login.php
```

### Logout Flow
```
1. /auth/logout.php
2. DELETE FROM remember_tokens WHERE user_id = ?
3. Delete cookie
4. session_destroy()
5. Redirect ke login
```

### Real-time Session Validation
Dari `footer.php`:
- `setInterval` 5 detik → fetch `/auth/check_session.php`
- Jika `valid: false` → tampilkan overlay + redirect ke login

---

## 3. HELPER FUNCTIONS

### Location: `/config/helpers.php`
```php
initialsFromName(string $name): string
    → Generate inisial dari nama (max 2 karakter)

avatarColorFromName(string $name): string
    → Generate warna avatar HSL berdasarkan hash nama

formatTanggalID($datetime): string
    → Format: "27 Feb 2026 14:30"

safeRegulation(PDO $pdo, string $code): int
    → Ambil harga dari medical_regulations
    → Jika RANGE → random antara min-max
    → Jika FIXED → return price_min

formatTanggalIndo($date): string
    → Format: "27 Feb 26"

dollar($amount): string
    → Format: "$1,000"
```

### Location: `/helpers/session_helper.php`
```php
forceReloadUserSession(PDO $pdo, int $userId): void
    → Reload user data dari DB → update $_SESSION['user_rh']
```

### Location: `/auth/csrf.php`
```php
generateCsrfToken(): string
validateCsrfToken(string $token): bool
csrfField(): string  // → <input type="hidden" name="csrf_token" value="...">
```

---

## 4. DATABASE CONFIG

### File: `/config/database.php`
```php
$DB_HOST = 'localhost';
$DB_NAME = 'farmasi_ems';
$DB_USER = 'root';
$DB_PASS = 'Jal&jar123';

// PDO connection:
new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
// Timezone: Asia/Jakarta (+07:00)
```

---

## 5. ASSETS - CSS & JS

### CSS Files (`/assets/css/`)
- `app.css` - Main styles
- `layout.css` - Grid, flexbox layout
- `components.css` - Cards, buttons, forms
- `login.css` - Login page specific
- `responsive.css` - Mobile breakpoints

### JS Files
**Local:**
- `/assets/js/app.js` - Sidebar toggle, notifications, heartbeat
- `/public/push-subscribe.js` - Service Worker

**CDN:**
- jQuery 3.7.1
- DataTables 1.13.8 + Buttons 2.4.2
- Chart.js 4.4.1

### Other Assets
- `/assets/logo.png` - Logo Roxwood Hospital
- `/assets/sound/notification.mp3` - Sound notifikasi

---

## 6. INCLUDE PATTERN

### Standard Dashboard File
```php
<?php
session_start();
require_once __DIR__ . '/../auth/auth_guard.php';        // #1 Auth
require_once __DIR__ . '/../config/database.php';         // #2 DB
require_once __DIR__ . '/../config/helpers.php';          // #3 Helpers
require_once __DIR__ . '/../config/date_range.php';       // #4 Date logic

$pageTitle = 'Page Title';
include __DIR__ . '/../partials/header.php';              // #5 HTML head
include __DIR__ . '/../partials/sidebar.php';             // #6 Nav

// ... content ...

include __DIR__ . '/../partials/footer.php';              // #7 Scripts
```

### Partials (`/partials/`)
- `header.php` - HTML head, CSS, topbar, notifications
- `sidebar.php` - Navigation menu
- `footer.php` - Scripts, session checker, closing tags

---

## 7. FOLDER STRUCTURE

```
/legacy
├── index.php                          (root redirect)
├── /auth                              (authentication)
├── /dashboard                         (main app - 42 files)
├── /public                            (no auth required)
├── /api                               (API endpoints)
├── /actions                           (AJAX handlers - 27 files)
├── /ajax                              (AJAX endpoints)
├── /config                            (configuration)
├── /helpers                           (helper functions)
├── /partials                          (view components)
├── /assets                            (CSS, JS, images, sounds)
├── /cron                              (scheduled tasks)
├── /backup
└── /storage                           (uploaded files)
```

---

## 8. SECURITY FEATURES

1. **CSRF Protection** - `auth/csrf.php`
2. **SQL Injection** - PDO prepared statements everywhere
3. **Session Hijacking** - Remember token dengan password hashing
4. **XSS** - `htmlspecialchars()` untuk output
5. **API Auth** - Bearer token + Client ID validation
6. **Session Timeout** - Real-time validation setiap 5 detik
7. **Double Login Prevention** - Remember token check
8. **Error Logging** - Log ke file, bukan tampilkan ke user

---

## 9. ACCESS CONTROL

### Trainee Restrictions
- **403 Forbidden** untuk `rekap_farmasi.php`
- Boleh akses dashboard utama
- Validasi di file:
  ```php
  if (strtolower($medicJabatan) === 'trainee') {
      http_response_code(403);
      // show error page
  }
  ```

---

## 10. SPECIAL LOGIC

### Date Range (`/config/date_range.php`)
- Generate `$rangeStart`, `$rangeEnd`, `$rangeLabel`
- Support: today, yesterday, this_week, last_week, this_month, dll
- Dipakai di seluruh halaman rekap/laporan

### Error Logging
- Location: `/storage/error_log.txt`
- Production-safe: `display_errors = 0`, `log_errors = 1`

### Notifications
- Inbox system dengan table `inbox`
- Real-time updates via heartbeat
- Sound notification (`/assets/sound/notification.mp3`)
