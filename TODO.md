# TODO - RHMC MIGRATION

## Phase 0 – Verifikasi Environment
- [x] Apache port 2035 → DocumentRoot = D:/Project/Web/rhmc/laravel/public
- [x] Apache port 2036 → DocumentRoot = D:/Project/Web/rhmc/legacy
- [x] http://localhost:2036 bisa dibuka dan menampilkan legacy
- [x] Folder D:\Project\Web\rhmc\legacy\ ada dan berisi file PHP
- [x] Folder D:\Project\Web\rhmc\laravel\ siap diisi (boleh kosong)

## Phase 1 – Full Legacy Scan
- [x] Scan routing (semua file .php yang accessible)
- [x] Scan include structure
- [x] Scan session & auth
- [x] Scan helper functions
- [x] Scan query per halaman
- [x] Scan CSS & JS
- [x] Output: legacy-analysis.md

## Phase 2 – File Mapping 1:1
- [x] Buat mapping setiap file legacy → route/controller/blade
- [x] Output: legacy-to-laravel-map.md

## Phase 3 – Install Laravel 12
- [x] Install Laravel 12 di D:\Project\Web\rhmc\laravel\
- [x] Hubungkan ke database lama via .env
- [x] Verifikasi http://localhost:2035 menampilkan Laravel default

## Phase 4 – Copy Legacy Assets
- [x] Copy semua CSS dari legacy → laravel/public/assets/legacy/
- [x] Copy semua JS dari legacy → laravel/public/assets/legacy/
- [x] Verifikasi file bisa diakses

## Phase 5 – Database Mirror Per Table
- [x] Baca file hark8423_ems.sql
- [x] Buat migration per tabel (34 tabel)
- [x] Buat model per tabel (34 model)
- [x] Jalankan migration

## Phase 6 – Auth & Dashboard Migration
- [x] Migrasi legacy/auth/login.php → AuthController@showLogin + login.blade.php
- [x] Migrasi legacy/auth/login_process.php → AuthController@login
- [x] Migrasi legacy/auth/register_process.php → AuthController@register
- [x] Migrasi legacy/auth/logout.php → AuthController@logout
- [x] Migrasi legacy/auth/check_session.php → AuthController@checkSession
- [x] Test login & register - Berhasil dengan sempurna!
- [x] Migrasi legacy/dashboard/index.php - ✅ COMPLETED
- [x] Buat DashboardController - ✅ COMPLETED
- [x] Buat dashboard/index.blade.php - ✅ COMPLETED
- [x] Buat partial header, sidebar, footer - ✅ COMPLETED
- [x] Bandingkan visual output - ✅ COMPLETED
- [x] **BUGS FIXED** - jQuery loading, Blade syntax, Chart.js, DataTables ✅
- [x] **ACTION CONTROLLER CREATED** - All actions working ✅
- [x] **Notifications enabled** - Farmasi notifications working ✅
- [x] **Inbox enabled** - Inbox polling, read, delete working ✅
- [x] **Heartbeat enabled** - Activity tracking working ✅
- [x] **Helper functions fixed** - getDateRangeData() errors resolved ✅

## Phase 7 – All Page Migration
- [x] **ROUND 1: Simple Pages** (5 halaman) - ✅ 100% COMPLETED
  - [x] 1. setting_akun.php - Profile update form ✅
  - [x] 2. ranking.php - Read-only ranking display ✅
  - [x] 3. regulasi.php - Read-only regulations display ✅
  - [x] 4. ems_services.php - EMS Services form & rekap ✅
  - [x] 5. identity_test.php - Identity OCR Scanner form ✅
- [x] **ROUND 2: Medium Complexity** (5 halaman) - ✅ 100% COMPLETED
  - [x] 1. konsumen.php - Sales data & Excel import ✅
  - [x] 2. manage_users.php - User management & Medical codes ✅
  - [x] 3. absensi_ems.php - Work hours leaderboard & realtime duration ✅
  - [x] 4. operasi_plastik.php - Plastic surgery request & approval ✅
  - [x] 5. events.php - Public event registration ✅
- [x] **ROUND 3: Complex Pages** (7 halaman+) - ✅ 100% COMPLETED
  - [x] rekap_farmasi.php - ✅ Early Completion
  - [x] candidates.php & recruitment - Multi-step/Complex list ✅
  - [x] gaji.php & rekap_gaji.php - Payroll logic ✅
  - [x] reimbursement.php - Expense tracking ✅
  - [x] restaurant_consumption.php - Inventory/Consumption ✅
  - [x] validasi.php - Final validation logic ✅
  - [x] rekap_farmasi_v2.php - ✅ COMPLETED (Controller + View created)
- [x] Validasi satu per satu - COMPLETED

## Phase 8 – Final Validation
- [x] Test login functionality
- [x] Test dashboard
- [x] Test semua Phase 7 pages:
  - [x] /dashboard/settings/akun
  - [x] /dashboard/ranking
  - [x] /dashboard/regulasi
  - [x] /dashboard/ems-services
  - [x] /dashboard/identity-test
  - [x] /dashboard/konsumen
  - [x] /dashboard/users
  - [x] /dashboard/absensi-ems
  - [x] /dashboard/operasi-plastik
  - [x] /dashboard/events
  - [x] /dashboard/candidates
  - [x] /dashboard/salary
  - [x] /dashboard/reimbursement
  - [x] /dashboard/restaurant
  - [x] /dashboard/validasi
  - [x] /dashboard/rekap-farmasi
  - [x] /dashboard/rekap-farmasi-v2
- [x] Verify visual output matches legacy
- [x] Verify semua fungsi bekerja
- [x] Fix remaining issues jika ada


## BUGS & HOTFIXES
- [x] **Logout Route** - Fixed MethodNotAllowedHttpException by supporting GET/POST methods.
- [x] **jQuery & DataTables Loading** - Fixed loading order
- [x] **Blade Syntax** - Fixed json_encode escaping
- [x] **Action Files 404** - Created ActionController
- [x] **getDateRangeData() not found** - Added global helper function
- [x] **require_once errors** - Removed all require_once statements
- [x] **Missing controller imports** - Added RestaurantController, ValidasiController imports
